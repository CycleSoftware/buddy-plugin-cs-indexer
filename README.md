# CS Manticore Search Buddy
Package:

https://packagist.org/packages/cyclesoftware/buddy-plugin-cs-indexer


## Usage


``
indexer rotate
``

``
indexer rotate index_name
``

``
indexer status
``

``
show indexer status
``

``
indexer nodeid
``

``
show unattached indexes
``


## Requirements
``
manticore-extra needs to be installed as it is needed bij manticore-executor with is used by manticore-buddy
``

## How to start

```
mysql -h0 -P9306
CREATE PLUGIN cyclesoftware/buddy-plugin-cs-indexer type 'buddy' VERSION 'dev-main';
```
