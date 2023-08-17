# To generate SQL from Laravel queries

## insert

```php
use Hogus\Query\GrammarGenerate;

$values = [
    'name' => 'Tom',
    'age' => 12
];

$generate = new GrammarGenerate();
$generate->insert('user', $values)->save('user_insert');
// insert into `user` (`name`, `age`) values ('Tom', '12');
```

## update

```php
$values = [
    'name' => 'Tom',
    'age' => 12
];

$generate->update('user', $values, ['id' => 1]);
// or
$generate->update(DB::table('user')->where('id', 1), $values);
// save to file
$generate->save('user_sql');
// update `link_user` set `name` = 'Tom', `age` = '13' where (`id` = '1');
```

## delete

```php
$generate->delete('user', ['id' => 1]);
// or
$generate->delete(DB::table('user')->where('id', 1));

// save to file
$generate->save('user_sql');
//delete from `link_user` where `id` = '1';
```
## change

```php
$generate->change('user', function ($table) {
    $table->string('name', 50)->change();
    $table->integer('age', 11)->change();
});

//ALTER TABLE user CHANGE name name VARCHAR(50) CHARACTER SET utf8 DEFAULT '' NOT NULL COLLATE `utf8_general_ci`, CHANGE age age int(11) DEFAULT '0';
```

## save or dump

```php
# save
$generate->save($filename, $extension = 'sql'); // save to file

# dump
$generate->dump(); // print all sql
```