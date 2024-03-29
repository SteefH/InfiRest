InfiRest
========

InfiRest is a library for use with Zend Framework that enables you to easily
create REST interfaces, largely inspired by [django-tastypie][].

Also included is InfiRestDoctrine, for the creation of REST interfaces for your
[Doctrine 2][] entities.

Check out the [quickstart documentation][] for a gentle push in the right
direction.

Dependencies
------------

* PHP 5.3
* Zend Framework 1.11+ (might work with earlier versions).
* [Doctrine 2][], in case you want to use InfiRestDoctrine.

How this came to be
-------------------

Every year, my employer [Infi][] gives its individual employees one week to
work on a project of their own choice, the so-called hobby week. InfiRest was
my hobby week project for 2012.

I have used [Django][] for my own toy projects in the past, and recently I
messed a lot with front-end libraries like [backbone.js][] in combination with
[django-tastypie][] serving as the backend REST interface. Tastypie's ease of
use inspired me to create a similar library for Zend Framework, because we use
that framework a lot in our projects, and there is no extensive REST support
in the Zend Framework core library. I like to build stuff that simply works,
and I hope that this will be one of those things.

Work in progress
----------------

InfiRest, for now, is largely a work in progress. The goal I set for the end of
the hobby week was to have a straightforward working version with a REST-to-ORM
feature. Consequences are, among others, that the configurability of
the library could be a bit less limited. I plan to keep working on InfiRest to 
make it more mature, and ultimately, production ready.

Acknowledgements
----------------

* Infi for offering the time for this project.
* The authors and contributors of django-tastypie for making that great
  django app.

[django-tastypie]: https://github.com/toastdriven/django-tastypie
[Doctrine 2]: http://www.doctrine-project.org/
[quickstart documentation]: https://github.com/SteefH/InfiRest/blob/master/docs/Quickstart.md
[Infi]: http://infi.nl/
[Django]: http://www.djangoproject.com/
[backbone.js]: http://documentcloud.github.com/backbone/