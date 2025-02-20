<!DOCTYPE html>
<html>
<head>
    <title>Event Notification</title>
</head>
<body>
    {{-- <h1>Hello!</h1> --}}
    <h4>{{$subject}}</h4>
    <ul>
        <li><strong>Event Name:</strong> {{ $eventName }}</li>
        <li><strong>Date:</strong> {{ $eventDate }}</li>
        <li><strong>Location:</strong> {{ $eventLocation }}</li>
        <li><strong>participantName:</strong> {{ $participantName }}</li>
    </ul>
    <p>We look forward to seeing you there!</p>
</body>
</html>