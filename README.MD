# PO Merger for WP-CLI
When you are a translator contributor for a language that is similar to another one (example: French from France vs French Canada), merging the missing translation from the first language to the second one is a fast way to get translation done. Then, you simply have to edit the difference between the two locales.

This project was made to merge two WordPress PO files using a WP-CLI command.


## Installing
The project requires the latest version of WP-CLI. Start by updating WP-CLI in the terminal:

    wp cli update

Then run the following command to install the project:

    wp package install satellitewp/po-merger


## Using

PO Merger lets you merge PO files two different ways:

- With the URL of a project found on the WordPress repository
- With the translation URL of a project in the WordPress repository (including meta and apps)
- With the core version of WordPress

To merge two PO files from a WordPress project, use the following command:

    wp po merge <base-locale> <copy-locale> <source>

To merge two PO files from a WordPress core version, use the following command:

    wp po merge <base-locale> <copy-locale> <version>

## Parameters
| Parameter | Description |
| ------ | ------ |
| `<base-locale>` | Main locale used for the merge. |
| `<copy-locale>` | Locale used to get translations that are not present in the base locale. |
| `<source>` | Source can be a WordPress plugin or theme main page URL, a WordPress project translation URL (plugin, theme, meta or apps) or a WordPress core version. |


## Examples

Merging FR locale into FR-CA for a plugin or theme

    wp po merge fr-ca fr https://wordpress.org/plugins/wordpress-seo/

Using the translation URL

    wp po merge fr-ca fr https://translate.wordpress.org/locale/fr-ca/default/wp-plugins/wordpress-seo/
    
    wp po merge fr-ca fr https://translate.wordpress.org/locale/fr-ca/default/meta/wordcamp/
    
    wp po merge en-ca en-gb https://translate.wordpress.org/locale/en-ca/default/apps/ios/
    
Merging FR locale into FR-CA for WordPress 5.0

    wp po merge fr-ca fr 5.0

## Optional parameters
| Parameter | Description |
| ------ | ------ |
| `--fuzzy=<file>` | Specifies a text file containing words or expressions (one per line) that will trigger a translation revision. If a translation from the copy locale contains these strings, the translation will be marked as fuzzy.|
| `--mark-copy-as-fuzzy` | Marks the translations obtained from the copy locale as fuzzy.|
| `--mcaf` | Alias of the `--mark-copy-as-fuzzy` parameter. |
| `--status=<filter1>,<filter2>,...` |	Specifies the filter(s) for the base locale download URL. List of valid filters: current, untranslated, fuzzy, waiting, rejected, old. Some of the filters may be mutually exclusive.|
| `--diff-only` |	Generates a result PO file containing only the differential strings (untranslated).|
| `--username` | Get the translations from the specific contributor (WordPress.org username).|
| `--test` | Allows to conduct local tests. See Testing Locally section for details.|



## Testing the commands locally

A WordPress PO file may contain tens of thousands of lines and it can be difficult to verify the result of the merging process. Thus, the package allows to create small PO files locally and use them for merging.

The `--test` parameter allows to read PO files defined in the `tests/<base-locale>` and `tests/<copy-locale>` folders, instead of downloading from the WordPress server. The PO files should have the valid WordPress PO format and their names should contain exactly the same locale as their current folders, preceding by the -.

### Example
`tests/fr-ca/test-fuzzy-fr-ca.po` and `tests/fr/test-fuzzy-fr.po`

### Usage
`wp po merge <base locale> <copy-locale> https://wordpress.org/<type>/<name of the file>/ --test --<optional parameter>`

### Example
`wp po merge fr-ca fr https://wordpress.org/plugins/test-fuzzy/ --test`

### Parameters
| Parameter | Description |
| ------ | ------ |
|`<base-locale>` | The folder `tests/<base-locale>` |
|`<copy-locale>`	| The folder `tests/<copy-locale>` |
|`<type>` |	`plugins` or `themes` |
|`<name of the file>` |  The name of the file without the locale and PO extension |
|`<optional parameter>`	| Optional parameter |

In this example, the content of the files `tests/fr-ca/test-fuzzy-fr-ca.po` and `tests/fr/test-fuzzy-fr.po` will be processed. If the base locale doesn't have a given translation, it will be searched in the copy locale and if found, copied to the base locale. If in the base locale a given translation exists already, it won't be replaced by the translation from the copy locale.

The output will be saved in the directory where the command was executed, with the name `wp-<type>-<slug>-<base-locale>-merged.po`

### Example

    wp-plugins-test-fuzzy-fr-ca-merged.po

### Optional parameters
The optional parameters supported in the test mode are `--mark-copy-as-fuzzy/mcaf` and `--fuzzy=<file>`.

The package comes with the test PO files where you can add your own strings to test.

## Maintainers
This project was created and is maintained by [SatelliteWP](https://www.satellitewp.com/en?utm_source=po-merger). The project was started during an internship by Pavel Zaharciuc.

## Contributing
Ideas are welcome.
