<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Livewire\Livewire;

test('organizer can add a ticket type to their event', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '50')
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSet('ticketName', '')
        ->assertSee('3,000円')
        ->assertSee('50名');

    expect($event->ticketTypes()->sole())
        ->name->toBe('一般')
        ->price->toBe(3000)
        ->capacity->toBe(50);
});

test('ticket type input is validated', function (array $input, string $errorField) {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(array_merge(['ticketName' => '一般', 'ticketPrice' => '1000', 'ticketCapacity' => '10'], $input))
        ->call('addTicketType')
        ->assertHasErrors($errorField);

    expect(TicketType::count())->toBe(0);
})->with([
    'name is required' => [['ticketName' => ''], 'ticketName'],
    'price must not be negative' => [['ticketPrice' => '-1'], 'ticketPrice'],
    'capacity must be at least 1' => [['ticketCapacity' => '0'], 'ticketCapacity'],
]);

test('free ticket type is allowed', function () {
    $event = Event::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->set(['ticketName' => '招待', 'ticketPrice' => '0', 'ticketCapacity' => '5'])
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSee('0円');
});

test('other users cannot open the edit page to add ticket types', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('edit page lists only ticket types of that event', function () {
    $event = Event::factory()->create();
    TicketType::factory()->for($event)->create(['name' => '早割']);
    TicketType::factory()->create(['name' => '別イベント券']);
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->assertSee('早割')
        ->assertDontSee('別イベント券');
});

test('organizer can delete a ticket type of their event', function () {
    $ticketType = TicketType::factory()->create(['name' => '早割']);
    $this->actingAs($ticketType->event->user);

    Livewire::test('pages::events.edit', ['event' => $ticketType->event])
        ->call('deleteTicketType', $ticketType->id)
        ->assertDontSee('早割');

    expect(TicketType::count())->toBe(0);
});

test('ticket type of another event cannot be deleted from this edit page', function () {
    $event = Event::factory()->create();
    $otherTicketType = TicketType::factory()->create();
    $this->actingAs($event->user);

    Livewire::test('pages::events.edit', ['event' => $event])
        ->call('deleteTicketType', $otherTicketType->id)
        ->assertNotFound();

    expect(TicketType::count())->toBe(1);
});

test('only the organizer is allowed by the ticket type delete policy', function () {
    $ticketType = TicketType::factory()->create();

    expect($ticketType->event->user->can('delete', $ticketType))->toBeTrue()
        ->and(User::factory()->create()->can('delete', $ticketType))->toBeFalse();
});

test('event list shows the lowest ticket price', function (array $prices, string $expected) {
    $event = Event::factory()->create();
    foreach ($prices as $price) {
        TicketType::factory()->for($event)->create(['price' => $price]);
    }

    Livewire::test('pages::events.index')->assertSee($expected);
})->with([
    'lowest price is shown with a tilde' => [[3000, 1500], '1,500円〜'],
    'free when the lowest price is 0' => [[0, 2000], '無料'],
    'dash when there are no ticket types' => [[], '─'],
]);
