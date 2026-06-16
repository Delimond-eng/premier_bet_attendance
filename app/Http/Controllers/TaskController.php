<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Agent;
use App\Models\Station;
use App\Models\TaskSubtask;
use App\Models\TaskEvidence;
use App\Models\MobileDevice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TaskController extends Controller
{
    protected $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function index()
    {
        $agents = Agent::all();
        $stations = Station::all();
        return view('tasks.list', compact('agents', 'stations'));
    }

    public function fetchTasks(Request $request)
    {
        // On retire le GlobalScope pour permettre aux admins de voir tous les rapports
        $query = Task::withoutGlobalScope('manager_station')
            ->with(['agents', 'station', 'subtasks', 'evidences.agent']);

        if ($request->station_id) {
            $query->where('station_id', $request->station_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->agent_id) {
            $query->whereHas('agents', function($q) use ($request) {
                $q->where('agent_id', $request->agent_id);
            });
        }

        $from = $request->from ?: $request->start_date;
        $to = $request->to ?: $request->end_date;

        if ($from) {
            $query->whereDate('start_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('due_date', '<=', $to);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:tasks,id',
            'title' => 'required|string|max:255',
            'station_id' => 'nullable|exists:sites,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'priority' => 'required|in:low,medium,high',
            'description' => 'nullable|string',
            'is_global' => 'boolean',
            'agent_ids' => 'nullable|array',
            'subtasks' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $taskData = [
                'title' => $validated['title'],
                'station_id' => $validated['station_id'],
                'description' => $validated['description'],
                'priority' => $validated['priority'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'is_global' => $request->is_global ?? false,
            ];

            if (!empty($validated['id'])) {
                $task = Task::findOrFail($validated['id']);
                $task->update($taskData);
            } else {
                $taskData['status'] = 'pending';
                $task = Task::create($taskData);
            }

            if (!$task->is_global) {
                $task->agents()->sync($validated['agent_ids'] ?? []);
            } else {
                $task->agents()->detach();
            }

            if (isset($validated['subtasks'])) {
                $task->subtasks()->where('is_completed', false)->delete();
                foreach ($validated['subtasks'] as $stTitle) {
                    if (!empty($stTitle)) {
                        $task->subtasks()->create(['title' => $stTitle]);
                    }
                }
            }

            DB::commit();

            if (empty($validated['id'])) {
                try {
                    $this->sendTaskNotifications($task);
                } catch (\Exception $e) {
                    Log::warning("Notification FCM échouée mais tâche créée : " . $e->getMessage());
                }
            }

            return response()->json(['message' => 'Tâche enregistrée avec succès', 'task' => $task]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur enregistrement tâche: " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()], 500);
        }
    }

    public function getTerminalTasks(Request $request)
    {
        $stationId = $request->query('station_id');
        $matricule = $request->query('matricule');

        if (!$stationId || !$matricule) {
            return response()->json(['error' => 'station_id et matricule sont requis'], 400);
        }

        $query = Task::withoutGlobalScope('manager_station')
            ->with(['subtasks' => function($q) {
                $q->where('is_completed', false);
            }, 'agents', 'station'])
            ->where('station_id', $stationId)
            ->where(function($q) use ($matricule) {
                $q->where('is_global', true)
                  ->orWhereHas('agents', function($sq) use ($matricule) {
                      $sq->where('matricule', $matricule);
                  });
            });

        $tasks = $query->whereIn('status', ['pending', 'in_progress'])
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    public function completeTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'matricule' => 'required|string',
            'subtask_ids' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'image' => 'nullable|image|max:10240',
            'note' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $agent = Agent::where('matricule', $request->matricule)->first();
            if (!$agent) {
                return response()->json(['errors' => 'Agent non trouvé avec le matricule : ' . $request->matricule], 404);
            }

            $task = Task::findOrFail($request->task_id);

            $files = $request->file('images') ?: ($request->hasFile('image') ? [$request->file('image')] : []);

            foreach ($files as $file) {
                $filename = time() . '_' . uniqid('task_') . '.' . $file->getClientOriginalExtension();
                $destination = public_path('task_evidences');

                if (!file_exists($destination)) {
                    mkdir($destination, 0777, true);
                }

                $file->move($destination, $filename);
                $fullUrl = url('task_evidences/' . $filename);

                TaskEvidence::create([
                    'task_id' => $task->id,
                    'agent_id' => $agent->id,
                    'image_path' => $fullUrl,
                    'note' => $request->note
                ]);
            }

            if (!empty($request->subtask_ids)) {
                TaskSubtask::whereIn('id', $request->subtask_ids)
                    ->where('task_id', $task->id)
                    ->update([
                        'is_completed' => true,
                        'completed_at' => now()
                    ]);
            }

            $totalSubtasks = $task->subtasks()->count();
            $completedSubtasks = $task->subtasks()->where('is_completed', true)->count();

            if ($totalSubtasks > 0 && $completedSubtasks === $totalSubtasks) {
                $task->status = 'completed';
                $task->completed_at = now();
            } else {
                $task->status = 'in_progress';
            }
            $task->save();

            DB::commit();
            return response()->json([
                'message' => 'Mise à jour réussie',
                'status' => $task->status,
                'progress' => $task->progress
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur completion tâche: " . $e->getMessage());
            return response()->json(['errors' => 'Erreur lors du traitement'], 500);
        }
    }

    public function toggleSubtask(Request $request)
    {
        $request->validate([
            'subtask_id' => 'required|exists:task_subtasks,id',
            'is_completed' => 'required|boolean'
        ]);

        $subtask = TaskSubtask::findOrFail($request->subtask_id);
        $subtask->is_completed = $request->is_completed;
        $subtask->completed_at = $request->is_completed ? now() : null;
        $subtask->save();

        $task = $subtask->task;
        if ($task->status === 'pending' && $request->is_completed) {
            $task->status = 'in_progress';
            $task->save();
        }

        $total = $task->subtasks()->count();
        $done = $task->subtasks()->where('is_completed', true)->count();
        if ($total > 0 && $total === $done) {
            $task->status = 'completed';
            $task->completed_at = now();
            $task->save();
        }

        return response()->json(['message' => 'Statut mis à jour', 'progress' => $task->progress]);
    }

    public function monitoring()
    {
        $stations = Station::all();
        return view('tasks.monitoring', compact('stations'));
    }

    public function fetchMonitoringData(Request $request)
    {
        $query = Task::withoutGlobalScope('manager_station')->with(['subtasks', 'station']);

        if ($request->station_ids && is_array($request->station_ids)) {
            $query->whereIn('station_id', $request->station_ids);
        }

        $tasks = $query->get();
        $avgGlobalProgress = $tasks->count() > 0 ? round($tasks->avg('progress')) : 0;

        $stats = [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'overdue' => $tasks->filter(fn($t) => $t->is_overdue)->count(),
            'avg_global_progress' => $avgGlobalProgress
        ];

        $stationQuery = Station::withoutGlobalScope('manager_station')->has('tasks');
        if ($request->station_ids && is_array($request->station_ids)) {
            $stationQuery->whereIn('id', $request->station_ids);
        }

        $stationProgress = $stationQuery->get()
            ->map(function($s) {
                $stationTasks = Task::withoutGlobalScope('manager_station')->where('station_id', $s->id)->get();
                $avgProgress = $stationTasks->count() > 0 ? round($stationTasks->avg('progress')) : 0;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'tasks_count' => $stationTasks->count(),
                    'avg_progress' => $avgProgress,
                    'overdue_count' => $stationTasks->filter(fn($t) => $t->is_overdue)->count()
                ];
            });

        $evidenceQuery = TaskEvidence::with(['agent', 'task'])->latest();
        if ($request->station_ids && is_array($request->station_ids)) {
            $evidenceQuery->whereHas('task', function($q) use ($request) {
                $q->whereIn('station_id', $request->station_ids);
            });
        }

        $recentEvidences = $evidenceQuery->take(10)->get();

        return response()->json([
            'stats' => $stats,
            'stationProgress' => $stationProgress,
            'recentEvidences' => $recentEvidences
        ]);
    }

    public function reports()
    {
        $stations = Station::all();
        $agents = Agent::all();
        return view('tasks.reports', compact('stations', 'agents'));
    }

    public function agentTasksHistory(Request $request)
    {
        $agentId = $request->agent_id;
        $tasks = Task::whereHas('agents', function($q) use ($agentId) {
            $q->where('agent_id', $agentId);
        })->orWhere(function($q) {
            $q->where('is_global', true);
        })->with(['subtasks', 'station', 'evidences'])->latest()->get();

        return response()->json($tasks);
    }

    protected function sendTaskNotifications(Task $task)
    {
        $title = "📋 Nouvelle mission RD Tech";
        $body = "Tâche : " . $task->title . " (Priorité: " . strtoupper($task->priority) . ")";

        if ($task->is_global) {
            $devices = MobileDevice::where('station_id', $task->station_id)->get();
            foreach ($devices as $device) {
                if ($device->fcm_token) {
                    $this->fcm->notify($device->fcm_token, $title, $body);
                }
            }
        } else {
            foreach ($task->agents as $agent) {
                $device = MobileDevice::where('station_id', $task->station_id)->first();
                if ($device && $device->fcm_token) {
                    $this->fcm->notify($device->fcm_token, $title, $body);
                }
            }
        }
    }
}
