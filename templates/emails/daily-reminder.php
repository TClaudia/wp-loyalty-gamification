<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Reamintire Puncte Zilnice</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #7952b3;
            padding: 30px;
            text-align: center;
            color: white;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .points-box {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 2px dashed #7952b3;
            margin: 20px 0;
            border-radius: 5px;
        }
        .points-value {
            font-size: 24px;
            font-weight: 700;
            color: #7952b3;
            margin: 10px 0;
        }
        .streak-info {
            font-size: 16px;
            color: #5e3d8f;
            margin: 5px 0;
        }
        .button {
            display: inline-block;
            background-color: #7952b3;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #5e3d8f;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666666;
            font-size: 14px;
        }
        .reminder {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nu uita de punctele tale zilnice!</h1>
        </div>
        <div class="content">
            <p>Salut {customer_name},</p>
            
            <p>Nu uita să-ți revendici punctele de fidelitate zilnice de la {site_name} astăzi!</p>
            
            <div class="points-box">
                <p>Astăzi poți câștiga:</p>
                <div class="points-value">{today_points} puncte</div>
                <div class="streak-info">Serie curentă: Ziua {current_streak}</div>
            </div>
            
            <p>În prezent ai un total de <strong>{points_total} puncte</strong> în programul nostru de fidelitate.</p>
            
            <div class="reminder">
                <strong>Sfat:</strong> Verifică zilnic pentru a-ți menține seria și a primi puncte bonus!
            </div>
            
            <p style="text-align: center;">
                <a href="{login_url}" class="button">Vizitează Site-ul Pentru a Revendica</a>
            </p>
            
            <p>Autentifică-te în contul tău și verifică zilnic pentru a acumula puncte și a debloca recompense exclusive!</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 {site_name} | <a href="{login_url}">Contul Meu</a></p>
            <p style="font-size: 12px; color: #999;">
                Primești acest email pentru că ești membru al programului nostru de fidelitate.
                <br>Dacă nu dorești să mai primești astfel de notificări, poți modifica preferințele în contul tău.
            </p>
        </div>
    </div>
</body>
</html>