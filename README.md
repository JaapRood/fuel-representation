#Representation
### Representation is a FuelPHP package to format your API resources, Views for data!

* [By Jaap Rood](http://www.jaaprood.nl)

## Why you need this

I found myself needing this in projects where I use Fuel's REST controller. In order to add links to my resources (e.g. with a Post resource, the link to the author), I need to able to define the outgoing data properly. In the case of a User resource, you would like to remove the sensitive info like passwords.

To keep the representations of my resources consistent and managable, I created this class which takes data and restructures it. The restructuring takes place in templates you can predefine. It's borrowing code from View very heavily, but returns data instead of a string (like Config) and all the bits we don't need are stripped out.

## Usage

As code is being borrowed from the View class so heavily, Representation behaves in a very similair way.

```php
$example_data = array('cool' => 'stuff', 'happens' => '!');

$structured_data = Representation::forge('the_file', $example_data)->output();
```

or

```php
$rep = Representation::forge('the_file', $example_data);
$rep->cool = 'stuff';
$rep->happens = '!';

$structured_data = $rep->output();
```

To structure data, we need to know how this should look. In order to define this, representation files are used similarly to views. You input the data into a template that uses it to create the output you'd like. However, just like in config files, you return the data in the representation file.

These template files can contain any logic you'd like. You can choose to completely define your datastructure, or to just edit the one you have.


### Example: editing a user for use in an API

The call

```php
$user = array( // this could be anything, like a Model
  'id'        => 3,
  'name'      => 'Jaap Rood',
  'website'   => 'http://jaaprood.nl'
  'password'  => '40eac98fb9843982ea98b9caa'
);

$structured_data = Representation::forge('api/user', array('user' => $user))->output();
```

The representation file 'api/user'

```php
unset($user['password']);
$user['link'] = Uri::create('api/users/'. $user['id']);

return $user;
```

If you really start using this for alot of representations, making the output more explicit might be a good idea 

```php
// this way you always have control over the data you output
return array(
  'id'        => $user['id'],
  'name'      => $user['name'],
  'website'   => 'http://jaaprood.nl'
  'link'      => Uri::create('api/users/'. $user['id']);
);
```

### Models to array

From working with a couple of Javascript Frameworks, I know that when using the normal Model->to_array() method creates associative arrays. These, however, will not create native JSON arrays but instead objects with numerical keys. Many Javascript frameworks don't play nice with this.

In order to address this problem, and because I basically do it in this part of my Apps, I included a helper function to convert Models without using associative arrays (recursively).

It might be dirty, belong in the Format class, but it has served me well!

```php
$user = Model_User::find('all');

Representation::models_to_array($user);
```

## Helping out

Please let me know about bugs, problems, issues, ideas, cool features, awesome places to get pasta, etc!