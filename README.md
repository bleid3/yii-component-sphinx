# yii-component-sphinx
Query Builder to the sphinx like Yii::CDbCommand

### In config/main.php

```php
'components'=>array(
---------------------------------
'sphinx' => array(
            'class' => 'application.components.sphinx.AdvSphinxConnection',
            'server' => 'localhost',
            'port' => 9312,
        ),
---------------------------------
)
```

### Use
```php
 $sphinx = Yii::app()->componentName->createCommand();
 $sphinx->select('*');
 $sphinx->fromIndex('*'); //necessarily, you can set all index - *
 $sphinx->match($search, true); //necessarily, you can set an empty string $search = ''
 $sphinx->fieldMatch('field', 'value', 'AND');
 $sphinx->where('field1=value1 & field2=value2');
 $sphinx->addFilter('field', array(1,2));
 $sphinx->addFilter('category', 'nameCategory');
 $sphinx->addFilterRange('field', 0.2, 3);
 $sphinx->offset(1);
 $sphinx->limit(8);
 $sphinx->order('group', 'desc');
 $sphinx->group('group');
 $result = $sphinx->query(true); 
 ```
 
 **NOTE:**
 If in query set false then $result is raw result sphinx.
 If in query set true then in $list = $result['list'] is array rows. 
 
 Additional methods
-------------------

      andMatch
      orMatch
      andWhere
      orWhere
      escapeString
      getSelect
      setFieldWeights
      comment
      getWarning
      getError
