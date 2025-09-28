<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'project.view-any','project.view','project.create','project.update','project.delete','project.assign',
            'site.view','site.update',
            'wbs.create','wbs.update',
            'task.view-any','task.view','task.create','task.update','task.delete','task.assign','task.comment',
            'rfi.view-any','rfi.view','rfi.create','rfi.update','rfi.approve',
            'document.view-any','document.view','document.upload','document.update','document.approve','document.download',
            'timesheet.view-any','timesheet.view','timesheet.submit','timesheet.update','timesheet.approve','timesheet.export',
            'cost.view','cost.update','cost.approve','cost.export',
            'procurement.view','procurement.create','procurement.update','procurement.approve',
            'hse_incident.view','hse_incident.create','hse_incident.update','hse_incident.close',
            'qaqc_inspection.view','qaqc_inspection.create','qaqc_inspection.update','qaqc_inspection.close',
            'report.view','report.export',
            'user.manage','role.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roles = [
            'admin' => ['*'],
            'project_manager' => [
                'project.*','site.*','wbs.*','task.*','rfi.*',
                'document.*','timesheet.*','cost.*','procurement.*',
                'hse_incident.view','qaqc_inspection.view','report.*'
            ],
            'site_manager' => [
                'project.view','site.*','wbs.*','task.*',
                'document.view','document.upload','document.update',
                'timesheet.view','timesheet.submit','timesheet.update',
                'hse_incident.*','qaqc_inspection.*','report.view'
            ],
            'engineer' => [
                'project.view','site.view','wbs.create','wbs.update',
                'task.view','task.create','task.update','task.comment',
                'rfi.view','rfi.create','rfi.update',
                'document.view','document.upload','document.update',
                'report.view','report.export'
            ],
            'document_controller' => [
                'document.view-any','document.view','document.upload','document.update','document.approve','document.download','report.export'
            ],
            'cost_controller' => [
                'cost.*','project.view','report.view','report.export'
            ],
            'procurement' => [
                'procurement.*','project.view','document.view','document.download','report.export'
            ],
            'hse_manager' => [
                'hse_incident.*','project.view','site.view','report.view'
            ],
            'qaqc_manager' => [
                'qaqc_inspection.*','project.view','site.view','document.view','report.view'
            ],
            'foreman' => [
                'project.view','site.view','task.view','task.update','document.upload','timesheet.submit','hse_incident.create','qaqc_inspection.create','report.view'
            ],
            'worker' => [
                'project.view','site.view','task.view','task.update','document.upload','timesheet.submit'
            ],
            'subcontractor' => [
                'project.view','site.view','task.view','task.update','document.upload','timesheet.submit'
            ],
            'client' => [
                'project.view','task.view','rfi.view','document.view','document.download','document.approve','cost.view','report.view','report.export'
            ],
        ];

        foreach ($roles as $roleName => $grants) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if (in_array('*', $grants, true)) {
                $role->givePermissionTo(Permission::all());
                continue;
            }

            $expanded = collect($grants)->flatMap(function ($pattern) {
                if (!str_ends_with($pattern, '*')) {
                    return [$pattern];
                }
                $prefix = rtrim($pattern, '*');
                return Permission::where('name', 'like', "$prefix%")
                    ->pluck('name');
            })->unique()->values()->all();

            $role->syncPermissions($expanded);
        }
    }
}

