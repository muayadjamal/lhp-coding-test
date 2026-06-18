<x-mail::message>
# You're on the list! 🎉

Hi {{ $attendee->name }},

Thanks for registering for **{{ $event['title'] }}**. Here are the details:

<x-mail::panel>
**{{ $event['title'] }}**
@if($event['starts_at_local'])
🗓 {{ $event['starts_at_local'] }} ({{ $event['timezone'] }})
@endif
@if($event['location_label'])
📍 {{ $event['venue'] ? $event['venue'].' · ' : '' }}{{ $event['location_label'] }}
@endif
</x-mail::panel>

Your status: **{{ ucfirst($attendee->status) }}**

We'll send you a reminder as the event approaches — once **3 days before** and again **24 hours before**.

<x-mail::button :url="config('app.url').'/events/'.$event['id']">
View event
</x-mail::button>

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
