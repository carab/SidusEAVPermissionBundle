Sidus/EAVPermissionBundle Documentation
==================================

## Quick example

````yaml
sidus_eav_model:
    families:
        Post:
            attributeAsLabel: title
            attributes:
                title:
                    required: true

                content:
                    type: html

                publicationDate:
                    type: datetime
                    options:
                        permissions:
                            read: [ROLE_DATA_MANAGER]
                            edit: [ROLE_DATA_ADMIN]
````

If no permission option is set, it means the attribute will be editable for everyone.

If the attribute is readable but not editable, the form type will appear disabled
