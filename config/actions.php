<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    'dashboard_admin' => [
        'entity' => 'dashboard_admin',
        'label'  => 'Tableau de bord Vue globale',
        'actions' => ['view']
    ],


    /*
    |--------------------------------------------------------------------------
    | RESSOURCES HUMAINES
    |--------------------------------------------------------------------------
    */

    'agents' => [
        'entity' => 'agents',
        'label'  => 'Agents',
        'actions' => ['view','create','update','delete','export','import']
    ],

    'stations' => [
        'entity' => 'stations',
        'label'  => 'Stations',
        'actions' => ['view','create','update','delete']
    ],


    'horaires' => [
        'entity' => 'horaires',
        'label'  => 'Horaires',
        'actions' => ['view','create','update', 'delete']
    ],

    'groupes' => [
        'entity' => 'groupes',
        'label'  => 'Groupes',
        'actions' => ['view','create','update']
    ],

    'plannings' => [
        'entity' => 'plannings',
        'label'  => 'Plannings rotatifs',
        'actions' => ['view','create','update']
    ],

    'retards' => [
        'entity' => 'retards',
        'label'  => 'Retards',
        'actions' => ['view','create','export']
    ],

    'absences' => [
        'entity' => 'absences',
        'label'  => 'Absences',
        'actions' => ['view','create','update','delete','export']
    ],


    'conges' => [
        'entity' => 'conges',
        'label'  => 'Congés',
        'actions' => ['view','create','update','delete','export']
    ],

    /*
    |--------------------------------------------------------------------------
    | RAPPORTS
    |--------------------------------------------------------------------------
    */

    'rapport_presences' => [
        'entity' => 'rapport_presences',
        'label'  => 'Rapports Présences',
        'actions' => ['view','export']
    ],

    'rapport_conges' => [
        'entity' => 'rapport_conges',
        'label'  => 'Rapports Congés',
        'actions' => ['view','export']
    ],

    'rapport_retards' => [
        'entity' => 'rapport_retards',
        'label'  => 'Rapports Retards',
        'actions' => ['view','export']
    ],

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRATION
    |--------------------------------------------------------------------------
    */

    'users' => [
        'entity' => 'users',
        'label'  => 'Utilisateurs',
        'actions' => ['view','create','update','delete']
    ],

    'roles' => [
        'entity' => 'roles',
        'label'  => 'Rôles & permissions',
        'actions' => ['view','create','update','delete']
    ],

    'logs' => [
        'entity' => 'logs',
        'label'  => 'Journal d’accès',
        'actions' => ['view']
    ],


];
