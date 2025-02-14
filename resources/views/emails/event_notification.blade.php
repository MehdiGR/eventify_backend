<!DOCTYPE html>
<html>
<head>
    <title>Event Notification</title>
</head>
<body>
    <h1>Hello!</h1>
    <p>You have successfully registered for the following event:</p>
    <ul>
        <li><strong>Event Name:</strong> {{ $eventName }}</li>
        <li><strong>Date:</strong> {{ $eventDate }}</li>
        <li><strong>Location:</strong> {{ $eventLocation }}</li>
    </ul>
    <p>We look forward to seeing you there!</p>
</body>
</html>