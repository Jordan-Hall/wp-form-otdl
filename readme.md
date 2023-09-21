# One Time Download Link for WPForms

This WordPress plugin allows you to generate a unique one-time download link for a file in the media library through WPForms.

## Features

- **One-Time Links**: Generate unique download links that can only be used once.
- **Integration with WPForms**: Seamlessly generate download links via WPForms.
- **AWS S3 Support**: Fetch files directly from S3 if they are stored there.
- **CDN Support**: Can handle files that are served from a Content Delivery Network (CDN), such as Amazon CloudFront.

## Installation

1. Download the plugin zip file.
2. Navigate to `Plugins > Add New` in your WordPress dashboard.
3. Click `Upload Plugin` and choose the zip file you downloaded.
4. Activate the plugin.

## Usage

1. When creating or editing a WPForm, add a field where users can select a file from the media library.
2. In the notification or confirmation settings of WPForms, you can use the `{download_link}` smart tag to generate a unique download link for the selected file.
3. The user will receive a one-time download link after submitting the form.

## Configuration

For AWS S3 and CDN support, the plugin requires specific configurations:

- AWS S3: Ensure you have defined `OTDL_AWS_SETTINGS` in your `wp-config.php`. This should contain your AWS credentials, bucket name, and region.
- CDN: If you're using a CDN, ensure that the CDN domain is specified in the AWS settings or in the WP Offload Media plugin settings.

## Logging

The plugin comes with a logging utility that helps in debugging issues related to file download. Check the logs to get insights on any issues that might arise.

## Support

For support, please contact the [Jordan](mailto://jordan@libertyware.co.uk) or open an issue on [github](https://github.com/jordan-hall/wp-form-otdl)

