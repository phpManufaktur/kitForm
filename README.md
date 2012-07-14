### kitForm

kitForm is an extension for the Customer Relationship Management (CRM) [KeepInTouch] [3] and the Content Management Systems [WebsiteBaker] [1] or [LEPTON CMS] [2]. It enables the easy creation of **forms** and **dialogs** for the CMS and the CRM. 

#### Requirements

* minimum PHP 5.2.x
* using [WebsiteBaker] [1] 2.8.x _or_ [LEPTON CMS] [2] 1.x
* [dbConnect_LE] [3] installed 
* [Dwoo] [4] installed
* [DropletsExtension] [5] installed
* [KeepInTouch] [6] installed
* [kitTools] [7] installed
* [wbLib] [8] installed
* jQueryAdmin **uninstalled** (_deprecated_, replaced by [LibraryAdmin] [9])
* [LibraryAdmin] [9] installed
* [libJQuery] [9] installed

#### Downloads

* [dbConnect_LE] [10]
* [Dwoo] [11]
* [DropletsExtension] [12]
* [KeepInTouch] [13]
* [kitTools] [14]
* [wbLib] [15]
* [LibraryAdmin] [16]
* [libJQuery] [17]
* [kitForm] [18]

#### Installation

* download the actual [kitForm] [18] installation archive
* in CMS backend select the file from "Add-ons" -> "Modules" -> "Install module"

#### First Steps

In the backend select "Admin-Tools" -> "kitForm". You will find around a dozen predefined sample dialogs and forms with brief descriptions. Use this samples as base for your own forms and dialogs.

To use a form in frontend please remember the name of the form, i.e. `kit_feedback` and edit the page where to place the form with your WYSIWYG editor.

`kit_feedback` can be used to enable the visitor of your website to give you feedback, it shows a form to send feedback, shows the feedback thread, gather feedbacks at the KIT event log a.s.o.

**kitForm** uses the Droplet `kit_form` to place the forms, type in your WYSIWYG editor:

    [[kit_form?form=kit_feedback]]
    
that's really all, the form is placed at your website and will work!  

Please visit the [phpManufaktur] [19] to get more informations about **kitForm** and join the [Addons Support Group] [20].

[1]: http://websitebaker2.org "WebsiteBaker Content Management System"
[2]: http://lepton-cms.org "LEPTON CMS"
[3]: https://addons.phpmanufaktur.de/dbConnect_LE
[4]: https://addons.phpmanufaktur.de/Dwoo
[5]: https://addons.phpmanufaktur.de/DropletsExtension
[6]: https://addons.phpmanufaktur.de/KeepInTouch
[7]: https://addons.phpmanufaktur.de/kitTools
[8]: http://wblib.webbird.de/de/
[9]: http://jquery.lepton-cms.org/
[10]: https://addons.phpmanufaktur.de/download.php?file=dbConnect_LE
[11]: https://addons.phpmanufaktur.de/download.php?file=Dwoo
[12]: https://addons.phpmanufaktur.de/download.php?file=DropletsExtension
[13]: https://addons.phpmanufaktur.de/download.php?file=KeepInTouch
[14]: https://addons.phpmanufaktur.de/download.php?file=DropletsExtension
[15]: https://github.com/webbird/wblib/downloads
[16]: http://jquery.lepton-cms.org/modules/download_gallery/dlc.php?file=75&id=1318585713
[17]: http://jquery.lepton-cms.org/modules/download_gallery/dlc.php?file=76&id=1320743410
[18]: https://addons.phpmanufaktur.de/download.php?file=kitForm
[19]: https://addons.phpmanufaktur.de/kitForm
[20]: https://phpmanufaktur.de/support
