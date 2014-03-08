﻿.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
=============

The definition of the link layout differs for external, internal, mail and filelinks in detail. The possible value are
always ``image``, ``linkTag``, ``filesize`` and ``string``, the position of each element is given via an index. Note
that you can add as many elements as you want to.

For all types it's important to place the TypoScript at the right place, otherwise the extension will do nothing. By
default the extensions configuration is set in:

- tt_content.text.20.parseFunc.tags.link

- tt_content.text.20.parseFunc.tags.typolist.default.parseFunc.tags.link

- lib.parseFunc.tags.link

- lib.parseFunc_RTE.tags.link


**Separator**

Every element will be separated using the default separator which can be set using the separator keyword. If you do not
use this configuration value the elements are separated with a space character, to delete the separator set it to empty
(separator = "").


.. toctree::
	:maxdepth: 5
	:titlesonly:
	:glob:

	TxMlLinks/Index
	TxMlLinks/FileLinks
	TxMlLinks/NotFound
	TxMlLinks/External
	TxMlLinks/InternalMailto