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
}
