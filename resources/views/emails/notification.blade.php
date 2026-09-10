<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $notification->message }}</title>
<style>
    body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
    .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
    .header { background: #0B1E3D; padding: 24px 32px; }
    .header h1 { color: #fff; font-size: 18px; margin: 0; }
    .header span { color: #00A99D; }
    .body { padding: 28px 32px; }
    .message { font-size: 16px; color: #1f2937; margin-bottom: 24px; line-height: 1.6; }
    .btn { display: inline-block; background: #00A99D; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; }
    .footer { background: #f9fafb; padding: 16px 32px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Kathford <span>Process</span></h1>
    </div>
    <div class="body">
        <p class="message">{{ $notification->message }}</p>
        @if($notification->link)
        <a href="{{ url($notification->link) }}" class="btn">View Details</a>
        @endif
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Kathford International College · This is an automated notification.
    </div>
</div>
</body>
</html>
