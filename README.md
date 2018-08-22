# CakeResque

CakeResque is a CakePHP plugin for Resque, a library for creating background jobs that can be processed offline later.

[Resque](https://github.com/resque/resque) is a Redis-backed library for creating background jobs, placing
those jobs on one or more queues, and processing them later.

This plugin wraps the libary [dwmcnelis/resque](https://github.com/dwmcnelis/resque).

This library is a fork of [wa0x6e/Cake-Resque](https://github.com/wa0x6e/Cake-Resque).

## Requirements

* PHP 5.3+
* Cake 2.0
* Resque [dwmcnelis/resque](https://github.com/dwmcnelis/resque)
* A Redis client library (for instance, [Predis](https://github.com/nrk/predis) or [Credis](https://github.com/colinmollenhour/credis))

## Jobs

### Queueing Jobs

Jobs are queued as follows:

```php
use Resque\Resque;
use Predis\Client;

$resque = new Resque(new Client());
$resque->enqueue('default_queue', 'App\Job', array('foo' => 'bar'), true);
```

In order the arguments are: queue, job class, job payload, whether to enable tracking.

### Defining Jobs

Each job should be in its own class, and implement the `Resque\JobInterface`. (This is pretty easy,
and only really requires a single custom method: `perform()`.) Most of the time, you'll want to
use the default implementation of a Job, and extend the `Resque\AbstractJob` instead of implementing
the interface yourself:

```php
namespace App;

use Resque\AbstractJob;

class Job extends AbstractJob
{
    public function perform()
    {
        // work work work
        $this->doSomething($this->payload['foo']);
    }
}
```

Any exception thrown by a job will result in the job failing - be
careful here and make sure you handle the exceptions that shouldn't
result in a job failing.

### Job Status Tracking

vend/resque has the ability to perform basic status tracking of a queued
job. The status information will allow you to check if a job is in the
queue, is currently being run, has finished, or has failed.

To track the status of a job, pass `true` as the fourth argument to
`Resque\Resque::enqueue`. An ID used for tracking the job status will be
returned:

```php
$id = $resque->enqueue('default_queue', 'App\Job', $payload, true);
echo $id; // [0-9a-f]{32}
```

To fetch the status of a job:

```php
$factory = new Resque\Job\StatusFactory($resque);

// Pass the ID returned from enqueue
$status = $factory->forId($id);

// Alternatively, to get the status for a Job instance:
$status = $factory->forJob($job);

// Outputs the status as a string: 'waiting', 'running', 'complete', etc.
echo $status->getStatus();
```

The Status object contains methods for adding other attributes to the
tracked status. (For instance, you might use the status object to track
errors, completion information, iterations, etc.)

#### Statuses

Job statuses are defined as constants in the `Resque\Job\Status` class.
Valid statuses include:

* `Resque\Job\Status::STATUS_WAITING` - Job is still queued
* `Resque\Job\Status::STATUS_RUNNING` - Job is currently running
* `Resque\Job\Status::STATUS_FAILED` - Job has failed
* `Resque\Job\Status::STATUS_COMPLETE` - Job is complete

Statuses are available for up to 24 hours after a job has completed
or failed, and are then automatically expired. A status can also
forcefully be expired by calling the `stop()` method on a status
class.

## Console

You are free you implement your own daemonization/worker pool strategy by
subclassing the `Worker` class. For playing around, and low throughput
applications, you might like to use the built-in console commands.

This version of the library uses the Symfony2 Console component. But it
must be configured with the details of your Redis connection. We do this
in much the same way that the Doctrine2 Console component gets details
of your database connection: via a `cli-config.php` file.

There is an example `cli-config.php` in this repository.

If you're running a full-stack web application, you'd generally use your
locator/service container to fill in the Redis client connection in this
file. (The default is to use Predis, and to connect to 127.0.0.1:6379).

### Basic Usage

```
resque <subcommand>

Available commands:
  enqueue       Enqueues a job into a queue
  help          Displays help for a command
  list          Lists commands
  worker        Runs a Resque worker
queue
  queue:clear   Clears a specified queue
  queue:list    Outputs information about queues
```

### Enqueueing

This command will enqueue a `Some\Test\Job` job onto the `default` queue.
Watch out for the single quotes around the class name: when specifying backslashes
on the command line, you'll probably have to avoid your shell escaping them.

```
resque enqueue default 'Some\Test\Job' -t
```

### Worker

This command will run a simple pre-forking worker on two queues:

```
resque worker -Q default -Q some_other_queue
```

(`-q` means quiet, `-Q` specifies queues). You can also specify no queues, or
the special queue `'*'` (watch for shell expansion).


### Queue Information

There are a couple of useful commands for getting information about the queues. This
will show a list of queues and how many jobs are waiting on each:

```
resque queue:list
```

This command will clear a specified queue:

```
resque queue:clear default
```

### Logging

The library now uses PSR3. When running as a console component, you can customize
the logger to use in `cli-config.php`.
