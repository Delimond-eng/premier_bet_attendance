<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard_admin' => [
        'entity' => 'dashboard_admin',
        'label'  => 'Tableau de bord (vue globale)',
        'actions' => ['view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | RH / OPERATIONS
    |--------------------------------------------------------------------------
    */
    'agents' => [
        'entity' => 'agents',
        'label'  => 'Agents',
        'actions' => ['view', 'create', 'update', 'delete', 'export', 'import'],
    ],

    'stations' => [
        'entity' => 'stations',
        'label'  => 'Stations',
        'actions' => ['view', 'create', 'update', 'delete', 'export'],
    ],

    'horaires' => [
        'entity' => 'horaires',
        'label'  => 'Horaires',
        'actions' => ['view', 'create', 'update', 'delete', 'export'],
    ],

    'groupes' => [
        'entity' => 'groupes',
        'label'  => 'Groupes',
        'actions' => ['view', 'create', 'update'],
    ],

    'plannings' => [
        'entity' => 'plannings',
        'label'  => 'Plannings rotatifs',
        'actions' => ['view', 'create', 'update', 'import', 'export'],
    ],

    'presences' => [
        'entity' => 'presences',
        'label'  => 'Pointages',
        'actions' => ['view', 'create', 'export'],
    ],

    'retards' => [
        'entity' => 'retards',
        'label'  => 'Retards',
        'actions' => ['view', 'create', 'export'],
    ],

    'absences' => [
        'entity' => 'absences',
        'label'  => 'Absences',
        'actions' => ['view', 'create', 'update', 'delete', 'export'],
    ],

    'conges' => [
        'entity' => 'conges',
        'label'  => 'Conges',
        'actions' => ['view', 'create', 'update', 'delete', 'export'],
    ],

    'attributions' => [
        'entity' => 'attributions',
        'label'  => 'Attributions conge',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'authorizations' => [
        'entity' => 'authorizations',
        'label'  => 'Autorisations speciales',
        'actions' => ['view', 'create', 'update', 'delete', 'export'],
    ],

    'justifications' => [
        'entity' => 'justifications',
        'label'  => 'Justifications RH',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'timesheet' => [
        'entity' => 'timesheet',
        'label'  => 'Pointage mensuel RH',
        'actions' => ['view', 'export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */
    'rapport_presences' => [
        'entity' => 'rapport_presences',
        'label'  => 'Rapports presences',
        'actions' => ['view', 'export'],
    ],

    'rapport_absences' => [
        'entity' => 'rapport_absences',
        'label'  => 'Rapports absences',
        'actions' => ['view', 'export'],
    ],

    'rapport_conges' => [
        'entity' => 'rapport_conges',
        'label'  => 'Rapports conges',
        'actions' => ['view', 'export'],
    ],

    'rapport_retards' => [
        'entity' => 'rapport_retards',
        'label'  => 'Rapports retards',
        'actions' => ['view', 'export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION
    |--------------------------------------------------------------------------
    */
    'users' => [
        'entity' => 'users',
        'label'  => 'Utilisateurs',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'roles' => [
        'entity' => 'roles',
        'label'  => 'Roles & permissions',
        'actions' => ['view', 'create', 'update', 'delete'],
    ],

    'logs' => [
        'entity' => 'logs',
        'label'  => 'Journal acces',
        'actions' => ['view'],
    ],
];

