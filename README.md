# yii2-meta

This provides some helpful classes for when a system is designed in which one
database table has metadata kept on its records in another table.

MetaBehavior can be attached to models representing the meta table records
and provide some helpful functions and shortcuts for accessing that data

MetaHelper contains helper functions for the meta models or data

MetaModelTrait can be used by model classes alongside MetaBehavior to be able
to have each meta data have its own unique label, validation rules, etc.

MetaSaveAction is an action class that can be used for pages where meta data
models are being saved