# Instagram Auto Engagement PHP Script

This PHP script is designed to automatically interact with Instagram posts, including likes, views, impressions, story views, and more, based on specific tags and criteria. It integrates with the Instagram API and supports various SMM APIs, allowing you to automate multiple engagement actions after logging into your Instagram account.

## Features

- **Auto Likes**: Automatically likes posts based on specified hashtags.
- **Auto Views**: Automatically increases views on posts and videos.
- **Auto Impressions**: Boosts the number of impressions for your content, enhancing visibility.
- **Auto Story Views**: Automates story views to increase story engagement.
- **Auto Saves**: Automatically saves posts to enhance engagement metrics.
- **Easy Integration**: Seamlessly integrates with the Instagram API and SMM APIs.
- **Multiple SMM API Support**: Manage multiple SMM APIs for extended functionalities.
- **Simple Setup**: Quick and straightforward setup and configuration process.
- **Secure Management**: Secure database connections and password handling.
- **Comprehensive Admin Control**: Manage SMM API settings, users, and engagement actions through an intuitive admin interface.
- **Ideal for Continuous Engagement**: Perfect for users looking to continuously boost their Instagram profile engagement.

## Requirements

- PHP 7.3 or higher.
- MySQL/MariaDB database.
- cURL enabled on your server.
- An active Instagram account with API access.
- Access to a web server (e.g., Apache, Nginx).

## Installation

1. **Clone the Repository**: Download or clone the repository to your web server.
   
2. **Setup Instagram API**:
   - Create an Instagram API application.
   - Set the callback URL to: `http://[your-website]/[installation-directory]/cb.php`.

3. **Database Configuration**:
   - Create a new MySQL database and configure your connection details in `/includes/opencon.php`.
   - Execute the SQL script (`auto-likess.sql`) included in the repository to create the necessary tables.

4. **API Key and URL Configuration**:
   - Update `Api.php` with your Instagram API key and the appropriate endpoint URLs.

5. **Run `add_user.php`**:
   - This script will add the admin user with secure credentials to your database.

## Usage

1. **Login**:
   - Navigate to `login.php` to access the admin interface.
   - Use the credentials set during installation to log in.

2. **Add Instagram User**:
   - Use the form to add Instagram usernames for the auto engagement functionalities.
   - Select SMM API services to use for each user, including likes, views, impressions, saves, and story views.

3. **Manage SMM APIs**:
   - Add or manage SMM API settings directly through the admin interface.
   - Customize the minimum and maximum quantities for each service, enabling tailored engagement for each user.

4. **Run Cron Jobs**:
   - Two cron scripts (`cron.php` and `cron2.php`) handle regular checks and interactions with Instagram and SMM APIs.
   - Schedule these scripts using your server's cron scheduling tools (e.g., crontab) to ensure continuous engagement.

## Buy Instagram Autolikes & Continuous Engagement Boost

For those looking to **buy Instagram autolikes** and maintain continuous engagement on their profiles, this script offers a comprehensive automated solution tailored for your needs. This tool can also automate views, impressions, story views, and more to maximize your profile's engagement. Learn more about boosting your Instagram automatically by visiting our comprehensive guide: [Instagram Auto Continuous Engagement Package](https://myinstafollow.com/instagram-auto-continuous-like-package/).

## Security

- **Sensitive Data**: Keep sensitive information such as API keys secure and do not expose them publicly.
- **Passwords**: Use strong, unique passwords for your admin account.
- **Access Control**: Ensure your server and database are protected with appropriate access controls.

## Troubleshooting

- **Database Issues**: Verify database credentials and ensure all SQL tables are correctly set up.
- **API Errors**: Check that API keys are correct and have the necessary permissions.
- **Logging**: Logs are maintained in `logdetail.txt` and `logdetail2.txt` for debugging.

## Contributing

- Contributions, including bug fixes and new features, are welcome.
- Fork the repository and submit a pull request with your changes.

## License

This project is licensed under the Mozilla Public License, version 2.0. See the LICENSE file for full details.

## Contact

For any questions or issues, contact our support team at [support@myinstafollow.com](mailto:support@myinstafollow.com). For more information about our services, visit [myinstafollow.com](https://myinstafollow.com).
