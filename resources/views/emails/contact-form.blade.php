<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #007bff; }
        .value { background-color: white; padding: 10px; border-radius: 3px; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Contact Form Submission</h1>
            <p>You have received a new message from your website contact form.</p>
        </div>

        <div class="content">
            <div class="field">
                <div class="label">Name:</div>
                <div class="value">{{ $contactData['name'] }}</div>
            </div>

            <div class="field">
                <div class="label">Email:</div>
                <div class="value">{{ $contactData['email'] }}</div>
            </div>

            @if(isset($contactData['phone']) && !empty($contactData['phone']))
            <div class="field">
                <div class="label">Phone:</div>
                <div class="value">{{ $contactData['phone'] }}</div>
            </div>
            @endif

            <div class="field">
                <div class="label">Message:</div>
                <div class="value">{{ nl2br(e($contactData['message'])) }}</div>
            </div>

            <div class="field">
                <div class="label">Submitted At:</div>
                <div class="value">{{ now()->format('F j, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>
</body>
</html>