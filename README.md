# Instagram Auto Likes PHP Script

This PHP script is designed to automatically like posts on Instagram based on specific tags. It interacts with the Instagram API, allowing you to automate likes after logging into your Instagram account.

## Features

- Automatically likes posts based on specified hashtags.
- Easy integration with the Instagram API.
- Simple setup and configuration process.
- Secure database connections and password management.
- Manage SMM APIs for extended functionalities.
- Ideal for users who want to continuously boost their Instagram engagement automatically.

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
   - Use the form to add Instagram usernames for the auto-like functionality.
   - Select SMM API services to use for each user.

3. **Manage SMM APIs**:
   - Add or manage SMM API settings directly through the admin interface.
   - Customize the minimum and maximum quantities for each service.

4. **Run Cron Jobs**:
   - Two cron scripts (`cron.php` and `cron2.php`) handle regular checks and interactions with Instagram and SMM APIs.
   - Schedule these scripts using your server's cron scheduling tools (e.g., crontab).

## Buy Instagram Autolikes & Continuous Engagement Boost

For those looking to **buy Instagram autolikes** and maintain continuous engagement on their profiles, this script offers an automated solution tailored for your needs. Learn more about boosting your Instagram likes automatically by visiting our comprehensive guide: [Instagram Auto Continuous Like Package](https://myinstafollow.com/instagram-auto-continuous-like-package/).

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
