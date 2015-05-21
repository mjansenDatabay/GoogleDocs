# ILIAS Google Docs Plugin
An ILIAS repository object plugin for collaborative editing of Google Docs documents.

## Installation Instructions
1. Clone this repository to <ILIAS_DIRECTORY>/Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs
2. Login to ILIAS with an administrator account (e.g. root)
3. Select **Plugins** from the **Administration** main menu drop down.
4. Search the **GoogleDocs** plugin in the list of plugin and choose **Activate** from the **Actions** drop down.
5. Choose **Configure** from the **Actions** drop down and enter the required data.

## Information
* You will have to enter username and password of an existing Google account in the plugin administration. This account is used as the object owner of every Google Docs object created in the ILIAS repository. 
* The permission handling for documents is covered by the API automatically. Therefore ILIAS needs the Google account (not the password) of every enrolled user who joins a Google Docs object (only once).
* The plugin does not support a SSO.

## Known Issues

### General
* Since Google changed their authentication security policy you have to enable the access for less secure applications here: <https://www.google.com/settings/security/lesssecureapps>