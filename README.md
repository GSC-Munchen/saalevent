# saalevent
Typo3 Extension to display the actual or upcoming event in our Gelb-Schwarz-Casino München e.V. ballrooms. Events are taken from an .ics-File, accessible via an internet-connection. It is necessary that the extension can download it without needing a password or authenticate against the providing server.

As we have three ballrooms, you may need to adjust the software by yourself if you have more or less.

Published at https://gsc-muenchen.de.

## how to install
1. clone this repository
2. move /saalevent/ to your TYPO3 instance root_folder /packages/saalevent/
3. in your TYPO3 root folder run composer req saalevent/saalevent:@dev
4. in case needed, as composer was run as root: check your access rights for www-data. Otherwise, the package will not run
5. you have to adjust the URLs providing the .ics-files
6. you may have to adjust the pre-delivered templates in the extension /Private/Template folder
7. add the extension to your Layout
 

## Thanks
Thanks and a loud shout out the the TYPO3 team, making this fantastic CMS possible

## Compatibility
| Version | TYPO3 | PHP       | Remarks      |
|---------|-------|-----------|--------------|
| 1       | 14    | 8.4 - 8.5 | full release |

## Site-Set installation (TYPO3 v14)

This extension provides a Site-Set under `Configuration/Site/saalevent` containing TypoScript that you should import into your site's configuration.

Steps:

1. In the TYPO3 backend open **Site Management → Sites** and edit your site.
2. In the site's configuration find the **Includes** / **TypoScript** import area.
3. Import the following files from the extension (use `EXT:saalevent` paths):
	- `EXT:saalevent/Configuration/Site/saalevent/TypoScript/setup.typoscript`
	- `EXT:saalevent/Configuration/Site/saalevent/TypoScript/constants.typoscript`

After import the extension's CSS and TypoScript settings are active for that site.