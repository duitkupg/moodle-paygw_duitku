# Moodle Payment Gateway Duitku

Welcome to the Duitku plugin repository for Moodle. This plugin is a payment through the payment account in moodle. As direction from moodle documentation about plugin type paygw. Duitku attend a plugin to help you receive payment through Duitku.

## Steps you need to Integrate
1. Download and install the plugin.
2. If you haven't Duitku account then you need to register.
3. Grab your Duitku API key and merchant code.
4. Configure the Moodle payment account with Duitku keys and merchant code.
5. Add 'Enrolment on payment' to the Moodle courses that you want

### Installation
After you download the plugin.
1. First, you need to login as admin to your moodle site.
2. Then, go to **Site administration** -> **Plugins** -> **Install plugins**
3. You'll see the choose file button or you can drag and drop the plugin zip file to the box. Choose or drop the zip file plugin.
4. Then, click **install plugin from ZIP file**.
5. Then, click **continue** after installation complete.

### Create Duitku Account
> To create an account you may see it [here](https://docs.duitku.com/account/).

### Configure Moodle payment account
1. For the configuration, go to **Site administration** -> **Plugins** -> **Manage payment gateway**.
2. You should found Duitku on the list. Make sure it is enable.
3. Then go to **Site administration** -> **General** -> **Payments** -> **Payment accounts**
4. Create a payment account.
5. On the available payment account or the one that you've been created. Beside account name you'll see payment gateways column. Click on Duitku.
6. Set **Enable** to be checked.
7. Input **API key**, **merchant code**, and **expiry period** of your desired value.
8. Then don't forget to set it in the right **environment**.

>***Please note, if you set wrong environment the access would be denied on payment.*

### Add Enrolment on payment
1. Go to course that you desired to add a payment.
2. On inside the course go to **participants**.
3. On the **participants** page, click the actions menu and select **Enrolment methods**.
4. Choose **Enrolment on payment** from the Add dropdown menu.
5. Select a payment account that you've been enabled Duitku when on the configuration, amend the enrolment fee as necessary then click the button **Add method**.

## Details

Duitku offers payment in Rupiah currencies that supported with virtual accounts, QRIS, paylater, e-wallet, retail outlets and credit card around Indonesia.
You might visit our website at [www.duitku.com](https://www.duitku.com/) for further information.

This plugin uses the new payment gateway API available in Moodle 3.11 and higher. If you are running an older version of Moodle and would like to connect using an older enrolment style plugin - please see the enrol_duitku plugin in our github repository [here](https://github.com/duitkupg/moodle-enrol_duitku).
