# Packlink Demo App

## Running the app
To start the demo app, open the terminal in the folder where the run.sh file is
and run it with `sh ./run.sh`. This will start the development server on the location `localhost:7000`.

You can set up debug for it as you would for any other server.

To stop the server, just press `Ctrl + c`.

## Changing to a multi-store environment

To change the single-store environment to the multi-store enviroment and test multi-store configurations, 
set the multi-store environment in the `Packlink\DemoUI\Boostrap::initServices` method.
