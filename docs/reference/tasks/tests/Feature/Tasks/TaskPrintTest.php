<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

uses(RefreshDatabase::class);

/*
| The print page of a task and its PDF. The page prints with the browser; the
| PDF is made on the server (DOMPDF) from a Blade view laid out with tables.
*/

test('guests are redirected to the login page', function () {
    $task = Task::factory()->create();

    $this->get(route('tasks.print', $task))->assertRedirect(route('login'));
    $this->get(route('tasks.pdf', $task))->assertRedirect(route('login'));
});

test('a user sees the print page of their own task', function () {
    $task = Task::factory()->create(['title' => 'Buy milk', 'due_on' => '2026-11-10']);

    $this->actingAs($task->user)
        ->get(route('tasks.print', $task))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/print')
            ->where('task.id', $task->id)
            ->where('task.title', 'Buy milk')
            ->where('task.due_on', '2026-11-10')
            ->where('task.status_label', 'Open'));
});

test('another user\'s task has neither a print page nor a PDF', function () {
    $task = Task::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('tasks.print', $task))->assertForbidden();
    $this->actingAs($stranger)->get(route('tasks.pdf', $task))->assertForbidden();
});

test('the PDF shows the task\'s facts and is a download', function () {
    Pdf::fake();
    $task = Task::factory()->create(['title' => 'Buy milk', 'due_on' => '2026-11-10']);

    $this->actingAs($task->user)->get(route('tasks.pdf', $task))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->isDownload()
        && $pdf->contains(['Buy milk', 'Open', '10 Nov 2026']));
});

test('a task with no due date says so in the PDF', function () {
    Pdf::fake();
    $task = Task::factory()->create(['due_on' => null]);

    $this->actingAs($task->user)->get(route('tasks.pdf', $task))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('No due date'));
});

test('the response is a real PDF file made by DOMPDF, also for a title in Chinese', function () {
    config(['laravel-pdf.driver' => 'dompdf']);
    $task = Task::factory()->completed()->create(['title' => '买牛奶 & <b>milk</b>']);

    $response = $this->actingAs($task->user)->get(route('tasks.pdf', $task));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('task-'.$task->id.'.pdf')
        ->and(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});

test('the PDF view escapes the title', function () {
    Pdf::fake();
    $task = Task::factory()->create(['title' => '<script>alert(1)</script>']);

    $this->actingAs($task->user)->get(route('tasks.pdf', $task))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('&lt;script&gt;') && ! $pdf->contains('<script>'));
});
