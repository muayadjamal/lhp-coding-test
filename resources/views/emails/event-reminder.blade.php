<x-mail::message>
# Your event is {{ $when }} ⏰

Hi {{ $attendee->name }},

A quick reminder that you're registered for **{{ $event['title'] }}**, happening **{{ $when }}**.

<x-mail::panel>
**{{ $event['title'] }}**
@if($event['starts_at_local'])
🗓 {{ $event['starts_at_local'] }} ({{ $event['timezone'] }})
@endif
@if($event['location_label'])
📍 {{ $event['venue'] ? $event['venue'].' · ' : '' }}{{ $event['location_label'] }}
@endif
</x-mail::panel>

<x-mail::button :url="config('app.url').'/events/'.$event['id']">
View event
</x-mail::button>

Can't make it? You can let the organizer know any time.

See you soon,<br>
{{ config('app.name') }}
</x-mail::message>
