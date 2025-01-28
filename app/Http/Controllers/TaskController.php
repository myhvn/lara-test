<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\GoogleSheetsService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query();

        // Filters as a bonus;)
        if ($request->filled('title')) {
            $tasks->where('title', 'like', '%' . $request->input('title') . '%');
        }

        if ($request->filled('status')) {
            $tasks->where('status', $request->input('status'));
        }

        if ($request->filled('id')) {
            $tasks->where('id', $request->input('id'));
        }

        $tasks = $tasks->with('user')->paginate(10);

        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        return view('tasks.create');
    }

    public function store(Request $request, TelegramService $telegramService, GoogleSheetsService $googleSheetsService)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|in:completed,not_completed',
        ]);

        $task = Auth::user()->tasks()->create($data);

        // message form TG
        $message = "🆕 New Task:\n";
        $message .= "🆔 ID: {$task->id}\n";
        $message .= "📌 Title: {$task->title}\n";
        $message .= "📝 Description: {$task->description}\n";
        $message .= "👤 User: {$task->user->name}\n";
        $message .= "📅 Due Date: {$task->due_date}\n";
        $message .= "📊 Status: {$task->status}\n";

        // Send message to Telegram
        $telegramService->sendMessage($message);

        // Add a row to Google Sheets
        $googleSheetsService->addRow([
            $task->id,
            $task->title,
            $task->description,
            $task->user->name,
            $task->due_date,
            $task->status,
        ]);

        return redirect()->route('tasks.index')->with('success', 'Task created successfully');
    }

    public function edit(Task $task)
    {
        // Check for curr user
        if ($task->user_id !== Auth::id()) {
            return redirect()->route('tasks.index')->with('error', 'Access denied');
        }

        return view('tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task, TelegramService $telegramService, GoogleSheetsService $googleSheetsService)
    {
        if ($task->user_id !== Auth::id()) {
            return redirect()->route('tasks.index')->with('error', 'You do not have permission to update this task.');
        }

        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|in:completed,not_completed',
        ]);

        // Update task
        $task->update($data);

        // Format the updated task message for Telegram
        $message = "✅ Task Updated:\n";
        $message .= "🆔 ID: {$task->id}\n";
        $message .= "📌 Title: {$task->title}\n";
        $message .= "📝 Description: {$task->description}\n";
        $message .= "👤 User: {$task->user->name}\n";
        $message .= "📅 Due Date: {$task->due_date}\n";
        $message .= "📊 Status: {$task->status}\n";

        // Send the updated task message to Telegram
        $telegramService->sendMessage($message);

        // Update Google Sheets row
        $googleSheetsService->updateRow(
            $task->id,
            [
                $task->id,
                $task->title,
                $task->description,
                $task->user->name,
                $task->due_date,
                $task->status,
            ]
        );


        return redirect()->route('tasks.index')->with('success', 'Task successfully updated.');
    }


    public function destroy(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            return redirect()->route('tasks.index')->with('error', 'Access denied!');
        }

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully');
    }
}
