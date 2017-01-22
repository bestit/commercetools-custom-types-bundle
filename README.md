# commercetools-custom-types-bundle

Bundle to help you with the creation of custom types. It provides you with a symfony config param matching your custom 
type structure, which then is mirrored to your commercetools project.

## Installation

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require bestit/commercetools-custom-types-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new BestIt\CTCustomTypesBundle\BestItCTCustomTypesBundle(),
        );

        // ...
    }

    // ...
}
```

### Step 3: Configure

```yml
# Default configuration for extension with alias: "best_it_ct_custom_types"
best_it_ct_custom_types:

    # Add the types mainly documented under: <https://dev.commercetools.com/http-api-projects-types.html>
    types:

        # Prototype
        key:
            name:                 # Required

                # Prototype
                lang:                 ~
            description:          # Required

                # Prototype
                lang:                 ~

            # https://dev.commercetools.com/http-api-projects-custom-fields.html#customizable-resources
            resourceTypeIds:      [] # Required

            # http://dev.commercetools.com/http-api-projects-types.html#fielddefinition
            fieldDefinitions:     # Required

                # Prototype
                name:
                    type:                 # Required
                        name:                 ~ # One of "Boolean"; "String"; "LocalizedString"; "Enum"; "Number"; "Money"; "Date"; "T
ime"; "DateTime"; "Set", Required

                        # Specially used to the set type: <http://dev.commercetools.com/http-api-projects-types.html#settype>
                        elementType:
                            name:                 ~ # One of "Boolean"; "String"; "LocalizedString"; "Enum"; "Number"; "Money"; "Date"
; "Time"; "DateTime"; "Set", Required
                            values:

                                # Prototype
                                key:                  ~
                        values:

                            # Prototype
                            key:                  ~
                    required:             false # Required
                    inputHint:            ~ # One of "MultiLine"; "SingleLine", Required
                    label:                # Required

                        # Prototype
                        lang:                 ~

    # The shell command works on the complete set of types normally. To prevent side effects while changing or deleting types which ar
e "unknown at this moment" define a whitelist for types, on which you are allowed to work on.
    whitelist:            []
    commercetools_client_service: ~ # Required
```

## Usage

The following symfony command mirrors your types config to the commercetools database:

```console
$ php bin/console commercetools:process-custom-types [<whitelist>]
```

**It tries to delete every custom type, which is not declared in your config or excluded by the
 whitelist (config or pcre regex without delimiter).**

## Further Todos

* Add unittests
* Refactor the command
* better Support for complex tyes
