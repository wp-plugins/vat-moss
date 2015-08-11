=== VAT MOSS Returns ===
Author URI: http://www.wproute.com/
Plugin URI: http://www.wproute.com/wp-vat-moss-submissions/
Contributors: bseddon
Tags: VAT, HMRC, MOSS, M1SS, tax, EU, UKdigital vat, Easy Digital Downloads, edd, edd tax, edd vat, eu tax, eu vat, eu vat compliance, european tax, european vat, iva, iva ue, Mehrwertsteuer, mwst, taux de TVA, tax, TVA, VAT, vat compliance, vat moss, vat rates, vatmoss, WooCommerce
Requires at least: 3.9.2
Tested up to: 4.3
Stable Tag: 1.0.19
License: GNU Version 2 or Any Later Version

Vendors in all EU member states can create EU VAT MOSS report definitions from Easy Digital Downloads and Woo Commerce sales records and create upload files to be sent your tax authority.

== Description ==

Each quarter businesses selling to EU consumers must submit a MOSS report to one of the EU tax authorities, such as HMRC, to document sales to consumers in other EU member states. 
This plug-in integrates with Easy Digital Downloads and/or Woo Commerce so it is able retrieve relevant sales records from which to create the quarterly return.

If you are the owner of a UK-based shop you may also be interested in the plug-in that generates and submits the [quarterly EC Sales List](https://wordpress.org/plugins/vat-ecsl) (VAT101).

= Features =

**Select your e-commerce package**

	* Easy Digital Downloads or
	* Woo Commerce

**Create quarterly or monthly submissions**

	* Select the transactions to be included
	* The plugin will only present sales to EU consumers so you cannot select invalid sales records
	* Specify the quarter for the submission
	* Review the MOSS report and then generate an electronic upload files in a format suitable the Austrian, Belgian, Cypriot,
	  Danish, Estonian, German, Latvian, Lithuanian, Luxembourg, Polish and UK tax authorities (to be expanded as information and tax authority 
	  services becomes available)
	* Coming soon: Irish and Dutch
	* Coming later: France, Italy
	* The Greek, Spanish and Slovakian tax authorities do not intend to support electronic file uploads

**Videos**

	[Watch videos](https://www.wproute.com/wordpress-vat-moss-reporting/ "Videos showing the plug-in working") showing how to setup the plugin, create a report definition and generate an output file
	
**Generate a file you can upload to your tax authority**

[Buy credits](https://www.wproute.com/wordpress-vat-moss-reporting/ "Buy credits") to generate a file the format required by your tax authority.

== Frequently Asked Questions ==

= Q. Do I need to buy credits to use the plugin? =
A. You are able to create a submission that will list the transactions to be included in quarterly return without buying credits. However to generate a file you are able to upload to your tax authority you will need to buy one or more credits.

= Q. Do I need to buy a credit to test the generation of an upload file?
A. No, you are able to see a facsimile of the VAT MOSS return before you buy a credit.

== Installation ==

Install the plugin in the normal way then select the settings option option from the VAT MOSS menu added to the main WordPress menu.  Detailed [configuration and use instructions](http://www.wproute.com/wp-vat-moss-submissions/) can be found on our web site.

**Requires**

This plugin requires that you capture VAT information in a supported format such as the format created by the [Lyquidity VAT plugin for EDD](http://www.wproute.com/ "VAT for EDD") 
or the [WooCommerce EU VAT Compliance plugin "Premium version"](https://www.simbahosting.co.uk/s3/product/woocommerce-eu-vat-compliance/) 
or the [WooCommerce EU VAT Assistant](https://wordpress.org/plugins/woocommerce-eu-vat-assistant/).

== Screenshots ==

1. The first task is to define the settings that are common to all submissions.
2. The second task is to select the e-commerce package you are using.
3. The main screen shows a list of the existing submissions.
4. New definitions are created by specifying the correct header information, most of which is taken from the settings, and also select the sales transactions that should be includedin the submission

== Changelog ==

= 1.0 =
Initial version released

= 1.0.2 =

Update version number to 1.0.2
Added company name (needed by the Swedish upload format)
Corrected domain name use

= 1.0.3 =

Fixed a problem when deactivating

= 1.0.4 =

Fixed a problem with an invalid constant name in vatidvalidator.php

= 1.0.5 =

Extra protections against malicious execution

= 1.0.6 =

Small change to prevent js and css files being added to the front end

= 1.0.7 =

Added the ability to test an upload file generation.  The file will be created but the values will be zero

= 1.0.8 =

Changes to address problems with translatability

= 1.0.9 =

Added Finnish translation thanks to Ahri (www.swratkaisut.com)

= 1.0.10 =

Added support for the free WooCommerce VAT plugin by Aelia
Added an option to include non-virtual products (defaults to virtual only)
Added extra warnings if WooCommerce or EDD and a corresponding VAT compliance plugin is not installed and activated

= 1.0.10 =

Fixed transaction selection to correctly handle the existence of a VAT number

= 1.0.11 =

Change to readme.txt

= 1.0.12 =

Fixed the tests to confirm the existence of the Lyquidity plugin (EDD) or the Simba or EU VAT Assistant plugin (WooCommerce)

= 1.0.13 =

Wrong store URL included

= 1.0.14 =

Updated references to the service site
Updated to allow any number of credits in the same period

= 1.0.15 =

Updated add_query_arg calls to escape them as recommended by the WordPress advisory

= 1.0.16 =

Fixed text domain errors

= 1.0.17 =

Fixed problem preventing the generation of a summary report

= 1.0.18 =

Fixed problem introduced as part of the effort to implement the WordPress advisory on using escape functions

= 1.0.19 =

Fixed problem preventing any entered license key showing

== Upgrade Notice ==

Nothing here

== Languages ==

<p><b>AT/DE</b>&nbsp;Neues EU-Mehrwertsteuer MOSS Berichtsdefinitionen aus EasyDigitalDownloads und WooCommerce erstellen Sie Dateien hochladen zu richten an Ihren Steuerbehörde</p>
<p><b>CY</b>&nbsp;EasyDigitalDownloads ve WooCommerce satış kayıtları bir AB KDV Moss rapor tanımlarını oluşturun ve yük dosyaları oluşturmak , vergi dairesine gönderilmesi gerekir</p>
<p><b>DK</b>&nbsp;Opret en EU-moms Moss rapportdefinitioner fra EasyDigitalDownloads og WooCommerce salgsrekorder og skabe indlæse filer skal sendes til skattekontoret</p>
<p><b>EE</b>&nbsp;Loo EL-i käibemaksu MOSS aruande määratlusi EasyDigitalDownloads ja WooCommerce müügi arvestust ja luua laadida faile saata oma Maksuhaldur</p>
<p><b>FR/BE/LU</b>&nbsp;Créer UE TVA MOSS rapport définitions de EasyDigitalDownloads et WooCommerce records de ventes et de créer des fichiers de téléchargement pour être envoyé votre autorité fiscale</p>
<p><b>IT</b>&nbsp;Creare un EU VAT Moss definizioni dei report da EasyDigitalDownloads e record di vendite WooCommerce e creare file di carico deve essere inviato all'ufficio delle imposte</p>
<p><b>LI</b>&nbsp;Sukurti ES PVM Moss ataskaitos apibrėžimus iš EasyDigitalDownload ir WooCommerce pardavimų apskaitoje ir sukurti apkrovos failus turi būti siunčiami mokesčių inspekcijai</p>
<p><b>NL/BE</b>&nbsp;Maak EU BTW MOSS rapport definities van EasyDigitalDownloads en WooCommerce verkoop records en uploaden van bestanden maken om uw belastingdienst worden gezonden</p>
<p><b>PL</b>&nbsp;Załóż swoje definicje raportów VAT UE Moss z EasyDigitalDownloads i WooCommerce ewidencji sprzedaży i tworzenia plików obciążenia należy przesłać do urzędu skarbowego</p>
<p><b>SE</b>&nbsp;Leverantörer i alla EU: s medlemsstater kan skapa EU-moms MOSS rapportdefinitioner från EasyDigitalDownloads och WooCommerce försäljningsrekord och skapa ladda upp filer som ska skickas din skattemyndighet.</p>
