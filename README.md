# SlabPHP Session Manager

This session management library was built because I liked the idea of "flash data", and I wanted a set of re-usable session handlers that I could alternatively use in the native session handling system. I wanted those handlers to be embedded in an encapsulated object that managed the lifecycle of the handler for me but also managed the concept of flash data transparently.

Please see the main SlabPHP documentation for more information on the SlabPHP project.

## Installation

Include this library in your project

    composer require slabphp/session-manager

## Usage

General steps are to create a handler, instantiate a driver, use the driver to handle your sessions. The handlers themselves can be instantiated and then activated with just using the ->startNativeSession() method.

### File Handler Session

The file handler will use the built-in session save directory if you are using native session handling otherwise you may want do something like the following:

    $handler = new \Slab\Session\Handlers\File();

    $handler->setSavePath(ini_get('session.save_path'));

    $driver = new \Slab\Session\Driver();

    $driver
        ->setHandler($handler)
        ->start();

### Database Handler Session

Assuming you have a mysqli object already open and created, you can feed it's reference into a database handler.

    $handler = new \Slab\Session\Handlers\Database\MySQL();

    $handler->setDatabase($myMySQLiObject, 'databaseName', 'tableName', 'site.com');

    $driver = new \Slab\Session\Driver();

    $driver
        ->setHandler($handler)
        ->start();

### Reading and Writing Data

Assuming you stored the session driver somewhere and have a reference to it called $session in the current object context:

    // Will return a value if set, or false empty or not is set
    $variableValue = $this->session->get('variableName');

Will retrieve the stored value. Otherwise you can set it with:

    $this->session->set('variableName', 'some value');

You can delete data the data with ->delete().

#### Flash Data

Flash data is data that is set in a session and then deleted after the following request. You mark a variable as flash data by giving it an @ symbol as the first character.

For example, if you are on a postback controller and you set a value with:

    $this->session->set('@success', true);
    $this->redirect('/thanks');

On the following page you could read this flash var but it won't persist for anything longer than that next request. So in your view controller you could do something like:

    if ($this->session->get('@success')) $this->displaySuccessMessage();

Subsequent loads of that page will not have the @success var because it will be deleted.