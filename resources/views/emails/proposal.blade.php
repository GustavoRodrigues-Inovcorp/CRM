<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a1a2e; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">{{ config('app.name') }}</h2>
        </div>
        <div class="content">
            {!! nl2br(e($body)) !!}
        </div>
        <div class="footer">
            <p>Este email foi enviado através do {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>