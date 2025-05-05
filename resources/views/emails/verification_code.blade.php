<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваш сгенерированный пароль в BizTorg</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Banner Image -->
                    <tr>
                        <td align="center">
                            <img src="{{ asset('banner.jpg') }}" alt="Баннер BizTorg" width="510" style="display: block; max-width: 100%; height: 200px;">
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h1 style="font-size: 24px; color: #333333; margin: 0 0 20px;">Ваш пароль в BizTorg</h1>
                            <p style="font-size: 16px; color: #555555; line-height: 1.5; margin: 0 0 20px;">
                                Здравствуйте,
                            </p>
                            <p style="font-size: 16px; color: #555555; line-height: 1.5; margin: 0 0 20px;">
                                Ваш пароль для входа в аккаунт: <strong>{{ $email }}</strong>:
                            </p>
                            <p style="font-size: 24px; color: #007bff; font-weight: bold; text-align: center; margin: 20px 0;">
                                {{ $code }}
                            </p>
                            {{-- <p style="font-size: 16px; color: #555555; line-height: 1.5; margin: 0 0 20px;">
                                Этот пароль истекает через <strong>15 минут</strong>.
                            </p> --}}
                            <p style="font-size: 16px; color: #555555; line-height: 1.5; margin: 0;">
                                Если вы не запрашивали пароль, пожалуйста, проигнорируйте это письмо или свяжитесь с нашей службой поддержки.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                            <p style="font-size: 14px; color: #777777; margin: 0;">
                                © {{ date('Y') }} BizTorg. Все права защищены.
                            </p>
                            <p style="font-size: 14px; color: #777777; margin: 10px 0 0;">
                                <a href="https://biztorg.uz" style="color: #007bff; text-decoration: none;">Посетите наш сайт</a> | 
                                <a href="mailto:shortway.technology@gmail.com" style="color: #007bff; text-decoration: none;">Связаться с поддержкой</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>