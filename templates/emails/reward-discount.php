<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php esc_html_e('You\'ve Earned a Discount!', 'wc-loyalty-gamification'); ?></title>
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
        .coupon {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 2px dashed #7952b3;
            margin: 20px 0;
            border-radius: 5px;
        }
        .coupon-code {
            font-size: 24px;
            font-weight: 700;
            color: #7952b3;
            margin: 10px 0;
            letter-spacing: 1px;
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
            <p>
                <?php 
                printf(
                    esc_html__('You\'ve earned a %d%% discount through our loyalty program!', 'wc-loyalty-gamification'),
                    '{discount_amount}'
                ); 
                ?>
            </p>
            
            <p><?php esc_html_e('As a valued customer, we\'re excited to reward you with this special discount on your next purchase.', 'wc-loyalty-gamification'); ?></p>
            
            <div class="coupon">
                <p><?php esc_html_e('Your Coupon Code:', 'wc-loyalty-gamification'); ?></p>
                <div class="coupon-code">{coupon_code}</div>
                <p>
                    <?php 
                    printf(
                        esc_html__('Valid until: %s', 'wc-loyalty-gamification'),
                        '{expiry_date}'
                    ); 
                    ?>
                </p>
            </div>
            
            <p><?php esc_html_e('Simply enter this code at checkout to claim your discount.', 'wc-loyalty-gamification'); ?></p>
            
            <p style="text-align: center;">
                <a href="{site_url}" class="button"><?php esc_html_e('Shop Now', 'wc-loyalty-gamification'); ?></a>
            </p>
            
            <p><?php esc_html_e('Thank you for being a loyal customer!', 'wc-loyalty-gamification'); ?></p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> {site_name} | <a href="{account_url}"><?php esc_html_e('My Account', 'wc-loyalty-gamification'); ?></a></p>
        </div>
    </div>
</body>
</html>