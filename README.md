# Duitku Payment Plugin for JoomShopping

A payment gateway integration for JoomShopping that enables secure payments through Duitku's platform.

## Features

- Secure payment processing through Duitku POP API
- Multiple payment methods support (Credit Card, QRIS, Paylater, E-money, VA, etc.)
- Comprehensive logging for debugging
- Easy configuration through JoomShopping admin panel
- Production and sandbox environment support

## Tested on

- Joomla 5.x
- PHP 8.2 or higher

## Installation

1. Download the latest release ZIP file from GitHub releases
2. Go to **Components > JoomShopping > Install & Update** in your Joomla admin
3. Choose "Upload Package File"
4. Select the downloaded ZIP file and install
5. The installation process will automatically to create the necessary database tables and configuration files.

## Configuration

After installation, configure the plugin:

1. Go to **JoomShopping > Payment Methods**
2. Find and edit the **Duitku** payment method
3. Configure the required settings:
   - **Merchant Code**: Your Duitku merchant code
   - **API Key**: Your Duitku API key
   - **Environment**: Choose Sandbox or Production

## Supported Payment Methods

For a complete list of supported payment methods, refer to the [Duitku POP API documentation](https://docs.duitku.com/pop/id/#payment-method).

## Testing

1. Configure test credentials from Duitku sandbox
2. Test payment flow with different payment methods
3. Monitor `duitku.log` for execution traces

## Troubleshooting

### Common Issues

- **Payment method not showing?**

  - Verify the plugin files are properly installed
  - Check `duitku.log` for installation errors

- **Callback not working?**

  - Ensure your server is accessible from the internet
  - Check `duitku.log` for callback logs

- **Payment amount issues?**

  - Check `duitku.log` for calculation details
  - Verify order totals in JoomShopping

### Logging

To access log file, navigate to `{root}/administrator/index.php?option=com_jshopping&controller=logs` since it doesn't support interface (in Joomla 5.3.2). Log file contains information about:

- API communication
- Callback handling
- Error conditions

## Support

- **Documentation**: [Duitku Developer Docs](https://docs.duitku.com)
- **Issues**: Report bugs via GitHub Issues
- **Duitku Support**: support@duitku.com

## License

GNU General Public License version 2 or later - See LICENSE.md
