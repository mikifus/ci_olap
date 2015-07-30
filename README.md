# ci_olap #
Experimental PHP library for CodeIgniter to collect and read statistical data in a star model.

The aim of this library is to collect statistics in a sorted and optimized way to stablish multi-dimensional "cubes". This format allows to slice, dice and drilldown the data.

## Technology ##

As for now, it only provides support for PostgreSQL database. Stored procedures and database functions are key in the behavior of the library, as the data is transformed by the database (E-LT).