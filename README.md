# Duitku Payment Plugin for JoomShopping

A payment gateway integration for JoomShopping that enables secure payments through Duitku's platform.

## Features

- Secure payment processing through Duitku POP API
- Multiple payment methods support (Credit Card, QRIS, Paylater, E-money, VA, etc.)
- Real-time transaction status updates
- Comprehensive logging for debugging
- Easy configuration through JoomShopping admin panel
- Production and sandbox environment support

## Requirements

- Joomla 5.x
- PHP 5.2.1 or higher

## Installation

1. Download the latest release ZIP file from GitHub releases
2. Go to **Components > JoomShopping > Install & Update** in your Joomla admin
3. Choose "Upload Package File"
4. Select the downloaded ZIP file and install

## Configuration

After installation, configure the plugin:

1. Go to **JoomShopping > Payment Methods**
2. Find and edit the **Duitku** payment method
3. Configure the required settings:
   - **Merchant Code**: Your Duitku merchant code
   - **API Key**: Your Duitku API key
   - **Environment**: Choose Sandbox (testing) or Production (live)
   - **Development URL**: (Optional) Your ngrok/tunnel URL for local development
   - **Transaction End Status**: Order status for successful payments
   - **Transaction Failed Status**: Order status for failed payments

## Supported Payment Methods

For a complete and up-to-date list of supported payment methods, refer to the [Duitku POP API documentation](https://docs.duitku.com/pop/id/#payment-method).

## Testing

1. Install the package on a development site
2. Configure test credentials from Duitku sandbox
3. Test payment flow with different payment methods
4. Monitor `duitku.log` for detailed execution traces
5. Verify callback handling with webhook testing tools

## Troubleshooting

### Common Issues

- **Payment method not showing?**

  - Check file permissions and JoomShopping configuration
  - Verify the plugin is installed and enabled
  - Check `duitku.log` for installation errors

- **Callback not working?**

  - Ensure your server is accessible from the internet
  - Check `duitku.log` for callback reception logs
  - Verify callback URL configuration in development environment

- **Installation fails?**

  - Ensure JoomShopping component is installed first
  - Check database permissions for payment method creation
  - Review installation logs in Joomla

- **Payment amount mismatch errors?**
  - The plugin automatically handles floating point precision issues
  - Check `duitku.log` for item detail calculations and adjustments
  - Verify order total calculations in JShopping

### Logging

All plugin activity is logged to `components/com_jshopping/log/duitku.log` with detailed information:

- Payment processing steps
- API communication
- Callback handling
- Error conditions
- Item detail calculations

**Viewing Logs:**

- **Admin Interface**: Go to `administrator/index.php?option=com_jshopping&controller=logs` to view logs through JShopping admin
- **File System**: Direct access at `components/com_jshopping/log/duitku.log`

## Support

- **Documentation**: [Duitku Developer Docs](https://docs.duitku.com)
- **Issues**: Report bugs via GitHub Issues
- **Duitku Support**: support@duitku.com

## License

GNU General Public License version 2 or later

## Changelog

### Version 1.0.0

- Initial release with Duitku POP API integration
- Support for multiple payment methods
- Automatic payment method creation during installation
- Comprehensive logging and error handling
- Production and sandbox environment support
