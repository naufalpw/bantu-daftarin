<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminActivityPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category', 'all')->toString();
        $categories = AdminActivityPresenter::categories();
        $category = array_key_exists($category, $categories) ? $category : 'all';
        $activities = AdminActivityPresenter::paginate($category);
        $activities->appends(['category' => $category]);

        return view('admin.activity.index', compact('activities', 'category', 'categories'));
    }
}
