<!DOCTYPE html>
<html>
<head>
    <title>{{ __('event.new_event_notification') }}</title>
</head>
<body>
    <h1>{{ __('event.new_event_notification') }}</h1>
    <p>{{ __('event.new_event_message', ['name' => $eventName]) }}</p>
    <p><strong>{{ __('event.description') }}:</strong> {{ $eventDescription }}</p>
    <p><strong>{{ __('event.start_date') }}:</strong> {{ $eventStartDate }}</p>
    <p><strong>{{ __('event.end_date') }}:</strong> {{ $eventEndDate }}</p>
</body>
</html>