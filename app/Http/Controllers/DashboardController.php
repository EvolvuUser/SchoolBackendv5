<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class DashboardController extends Controller
{
    public function getDashboardStructure(Request $request)
    {
        $shortName = $request->input('short_name');

        if (array_key_exists($shortName, config('database.connections'))) {
            config(['database.default' => $shortName]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid school database'
            ], 400);
        }

        $role = $request->input('role');

        // 1. Dashboard
        $dashboard = DB::table('dashboards')
            ->where('role', $role)
            ->where('is_active', 'Y')
            ->first();

        if (!$dashboard) {
            return response()->json([
                'status' => false,
                'message' => 'Dashboard not found'
            ], 404);
        }

        // 2. Sections
        $sections = DB::table('dashboard_sections')
            ->where('dashboard_id', $dashboard->dashboard_id)
            ->orderBy('section_order')
            ->get();

        // 3. Widgets
        $widgets = DB::table('dashboard_widgets as dw')
            ->join('widgets as w', 'w.widget_id', '=', 'dw.widget_id')
            ->where('dw.dashboard_id', $dashboard->dashboard_id)
            ->select(
                'dw.id as dashboard_widget_id',
                'dw.section_id',
                'dw.pos_x',
                'dw.pos_y',
                'dw.width',
                'dw.height',
                'w.widget_key',
                'w.widget_name',
                'w.widget_type'
            )
            ->orderBy('dw.pos_y')
            ->orderBy('dw.pos_x')
            ->get();

        // 4. Group widgets under sections
        $sections = $sections->map(function ($section) use ($widgets) {
            return [
                'section_id' => $section->dashboard_section_id,
                'section_name' => $section->section_name,
                'section_order' => $section->section_order,
                'widgets' => $widgets
                    ->where('section_id', $section->dashboard_section_id)
                    ->values()
                    ->map(function ($widget) {
                        return [
                            'dashboard_widget_id' => $widget->dashboard_widget_id,
                            'widget_key' => $widget->widget_key,
                            'widget_name' => $widget->widget_name,
                            'widget_type' => $widget->widget_type,
                            'layout' => [
                                'x' => $widget->pos_x,
                                'y' => $widget->pos_y,
                                'w' => $widget->width,
                                'h' => $widget->height
                            ]
                        ];
                    })
            ];
        });

        return response()->json([
            'status' => true,
            'dashboard' => [
                'dashboard_id' => $dashboard->dashboard_id,
                'name' => $dashboard->Name,
                'role' => $dashboard->role
            ],
            'sections' => $sections
        ]);
    }

    public function saveDashboardWidgets(Request $request)
    {
        DB::beginTransaction();

        try {
            $dashboard = $request->dashboard;

            // 1. Check if dashboard already exists for role
            $existingDashboard = DB::table('dashboards')
                ->where('role', $dashboard['role'])
                ->first();

            if ($existingDashboard) {
                // UPDATE
                $dashboardId = $existingDashboard->dashboard_id;

                DB::table('dashboards')
                    ->where('dashboard_id', $dashboardId)
                    ->update([
                        'name' => $dashboard['name'],
                        'is_active' => 'Y'
                    ]);

                // Delete old layout
                DB::table('dashboard_sections')->where('dashboard_id', $dashboardId)->delete();
                DB::table('dashboard_widgets')->where('dashboard_id', $dashboardId)->delete();
            } else {
                // INSERT
                $dashboardId = DB::table('dashboards')->insertGetId([
                    'name' => $dashboard['name'],
                    'role' => $dashboard['role'],
                    'is_active' => 'Y'
                ]);
            }

            // 2. Insert Sections
            foreach ($request->sections as $section) {
                $sectionId = DB::table('dashboard_sections')->insertGetId([
                    'dashboard_id' => $dashboardId,
                    'section_name' => $section['section_name'],
                    'section_order' => $section['section_order']
                ]);

                // 3. Insert Widgets
                foreach ($section['widgets'] as $widget) {
                    DB::table('dashboard_widgets')->insert([
                        'dashboard_id' => $dashboardId,
                        'section_id' => $sectionId,
                        'widget_id' => $widget['dashboard_widget_id'],
                        'pos_x' => $widget['layout']['x'],
                        'pos_y' => $widget['layout']['y'],
                        'width' => $widget['layout']['w'],
                        'height' => $widget['layout']['h'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'dashboard_id' => $dashboardId,
                'message' => 'Dashboard saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addWidget(Request $request)
    {
        $request->validate([
            'widget_key' => 'required|unique:widgets,widget_key',
            'widget_name' => 'required',
            'widget_type' => 'required'
        ]);
        DB::table('widgets')->insert([
            'widget_key' => $request->widget_key,
            'widget_name' => $request->widget_name,
            'widget_type' => $request->widget_type
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Widget added successfully',
            'success' => true
        ]);
    }

    public function getWidgets(Request $request)
    {
        $query = DB::table('widgets')
            ->join('widget_type', 'widgets.widget_type', '=', 'widget_type.widget_type_id')
            ->select(
                'widgets.widget_id',
                'widgets.widget_key',
                'widgets.widget_name',
                'widgets.widget_type',
                'widget_type.widget_name as widget_type_name'
            );

        // Filter by widget type
        if ($request->widget_type) {
            $query->where('widgets.widget_type', $request->widget_type);
        }

        $widgets = $query->orderBy('widgets.widget_id', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data' => $widgets,
            'success' => true
        ]);
    }

    public function updateWidget(Request $request)
    {
        // Validate
        $request->validate([
            'widget_id' => 'required|exists:widgets,widget_id',
            'widget_key' => 'required|unique:widgets,widget_key,' . $request->widget_id . ',widget_id',
            'widget_name' => 'required',
            'widget_type' => 'required'
        ]);

        DB::table('widgets')
            ->where('widget_id', $request->widget_id)
            ->update([
                'widget_key' => $request->widget_key,
                'widget_name' => $request->widget_name,
                'widget_type' => $request->widget_type
            ]);

        return response()->json([
            'status' => 200,
            'message' => 'Widget updated successfully',
            'success' => true
        ]);
    }

    public function deleteWidget($id)
    {
        $isUsed = DB::table('dashboard_widgets')
            ->where('widget_id', $id)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 400,
                'message' => 'Widget is already used in dashboard. Cannot delete.',
                'success' => false
            ]);
        }

        DB::table('widgets')->where('widget_id', $id)->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Widget deleted successfully',
            'success' => true
        ]);
    }

    public function getWidgetsType(Request $request)
    {
        $widgets = DB::table('widget_type')
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $widgets,
            'success' => true
        ]);
    }

    public function getDashboards(Request $request)
    {
        $query = DB::table('dashboards as d')
            ->leftJoin('dashboard_sections as ds', 'd.dashboard_id', '=', 'ds.dashboard_id')
            ->leftJoin('dashboard_widgets as dw', 'd.dashboard_id', '=', 'dw.dashboard_id')
            ->select(
                'd.dashboard_id',
                'd.name',
                'd.role',
                'd.is_active',
                DB::raw('COUNT(DISTINCT ds.dashboard_section_id) as total_sections'),
                DB::raw('COUNT(DISTINCT dw.widget_id) as total_widgets')
            )
            ->groupBy('d.dashboard_id', 'd.name', 'd.role', 'd.is_active');

        $dashboards = $query->orderBy('d.dashboard_id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $dashboards
        ]);
    }
}
