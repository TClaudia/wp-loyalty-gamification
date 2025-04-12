<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php esc_html_e('You\'ve Earned a Free Product!', 'wc-loyalty-gamification'); ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
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
        .reward-box {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 2px dashed #7952b3;
            margin: 20px 0;
            border-radius: 5px;
        }
        .reward-text {
            font-size: 24px;
            font-weight: 700;
            color: #7952b3;
            margin: 10px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php esc_html_e('Congratulations!', 'wc-loyalty-gamification'); ?></h1>
        </div>
        <div class="content">
            <p><?php esc_html_e('You\'ve earned a free product through our loyalty program!', 'wc-loyalty-gamification'); ?></p>
            
            <p><?php esc_html_e('As a valued customer, we\'re excited to reward you with a free product of your choice.', 'wc-loyalty-gamification'); ?></p>
            
            <div class="reward-box">
                <p><?php esc_html_e('Your Reward:', 'wc-loyalty-gamification'); ?></p>
                <div class="reward-text"><?php esc_html_e('FREE PRODUCT', 'wc-loyalty-gamification'); ?></div>
                <p><?php esc_html_e('Click the button below to choose your free product.', 'wc-loyalty-gamification'); ?></p>
            </div>
            
            <p><?php esc_html_e('You can select from our curated list of products or choose from your wishlist items.', 'wc-loyalty-gamification'); ?></p>
            
            <p style="text-align: center;">
                <a href="{free_product_url}" class="button"><?php esc_html_e('Claim Your Free Product', 'wc-loyalty-gamification'); ?></a>
            </p>
            
            <p><?php esc_html_e('Thank you for being a loyal customer!', 'wc-loyalty-gamification'); ?></p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> {site_name} | <a href="{account_url}"><?php esc_html_e('My Account', 'wc-loyalty-gamification'); ?></a></p>
        </div>
    </div>
</body>
</html>