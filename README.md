# CodeIgniter OLAP (ci_olap) #
Experimental PHP library for CodeIgniter to collect and read statistical data in a star model.

The aim of this library is to collect statistics in a sorted and optimized way to stablish multi-dimensional "cubes". This format allows to slice, dice and drilldown the data.

# Technology #
PHP 5.3+ is required.
As for now, it only provides support for PostgreSQL database. Stored procedures and database functions are key in the behavior of the library, as the data is transformed by the database (E-LT).

# Installation #
Use Composer or just add the source to your project.

## Setup ##
The implementation is divided in three steps.

### 1. Prepare the configuration file ###
The configuration file, located in "src/config/olap.php", must be added to the config folder on your CodeIgniter application folder. The 'cubes' array is the first point on which you may focus.

After the cubes are set up, you may add some dimensions. Those

Before that I want to call the attention on the end of the file where some prefix strings are set. These are merely decorative elements for your database to look nicer and avoid collisions of table names. You can leave this blank if you want. If you are using PostgreSQL you might want to set up an schema for these tables rather than using prefixes.

### 2. Prepare the database ###
Here you might need some advanced knowledge on stored procedures. The reason is performance, but as well encapsulation and because it simplifies a lot the PHP side of the library.

You can take as reference the example file in the src/sql folder. Just take a look on how the configuration file and the sql file names are related. Not only stored procedures but also views will allow you to manipulate the data to make magic.

### 3. Implement ###
It is up to you how to do this. Please take a close look to the Olap.php file and its methods to learn how to use it. It is as simple as using two methods: add and query. "Add" inserts data into the database, "query" reads it.

You can instantiate the library like this:
```php
// $this->db is a CodeIgniter database object
$olap = new \Olap\Olap( $this->db );
```
Or even create a CodeIgniter library script keep it beautiful:
```php
$this->load->library('olap', array( 'db' => $this->db ));
```

### Cube ###
Check out the example_config.php file. In this example you have a cube ready to work. The cube is mainly has four components: fact, views, measures and dimensions.

These components' names and descriptions have to represent the database tables and its columns, as well as the behavior of the dimensions (more on this later).

The example fact table would look like this:
```sql
CREATE TABLE olap_f_sale (
    price    INT NOT NULL,
    shop     INT NOT NULL,
    product  INT NOT NULL,
    time     INT NOT NULL,
);
```

#### Fact ####
The fact is defined as the event you want to store in the database. In the example we store 'sales'. This string will be used as a database table name, with its prefix, keep this in mind.

#### Views ####
The library collects the data from database tables or views, you can specify here the available views. If a table or a view is not specified here, the library won't be allowed to access it.

You will use the views as parameters when requesting your data back.

#### Measures ####
A measures must be a numeric column on your fact table. The measure is the number that is interesting about this fact. In this case, the price of each sale. You can add as many measures as you want, just remember that measures must be related to each other in the same event.

Moreover the library allows you to use the measure values to make queries. You can slice the data for values equal to x, higher than x, between x and y, etc.

In this case for example you could add a "tax" measure if each sale has a different taxation so you can later operate with clean data. Or even count the sales with only x% taxation.

#### Dimensions ####
This is where it gets complicated. Our cube can have many dimensions, more than 3, this is why, in fact, it is a hypercube. Dimensions are not just a numeric value, they can be anything related to the fact. You can query by dimensions values, or sort the data with them, or count them, just like measures.

You are free to choose to use actual database values that can be used later for joinin tables or any arbitrary value.

Dimensions can have a hierarchy, in which they are connected to each other in a one-to-many relationship. This must be specified in the dimension data in the configuration file.

In our example the dimensions are shops and products stored by their ID. We could add many more, like a customer ID, the product color, etc.

Wait! What is that "time" string over there? Continue reading.

### Dimensions hierarchy ###
Dimensions hierarchies can be stablished in the configuration file definition. Just add a list of the dimensions under the current one. Try not to create a recursive relationship and remember that they can only be one-to-many, otherwise they're just not hierarchical.

In the example file, the 'time' preset is composed by a set of four dimensions. The hierarchy in here is pretty clear. A common example of this is to use location information, so the hierarchy would be something like Planet > Country > Region > City.

### Dimensions unified fields ###
This might be the most confusing feature. Sometimes you don't feel like introducing the data by small chunks. Actually you will do this, but through an stored procedure.

Databases have functions to extract and operate with the data, even with better performance than PHP, so it is interesting to divide the data with SQL functions rather than concatenating parameters in PHP.

In our example you can see how "time" is a unified field, which means that you can just send a timestamp instead of sending the date by parts. Another interesting add would be a unified field for geographical coordinates, specially useful when combining this with PostGIS.