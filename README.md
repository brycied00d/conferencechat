# Twilio SMS Conference Chat #

This is an SMS handler for Twilio to distribute SMS messages from one person to 
a distribution list.

## Configuration ##
Configuration details are stored in `config.inc.php`. A sample has been provided
(`config.inc.php.sample`) - copy it to `config.inc.php` and follow the comments 
to customize it.

## Features ##
 * Verfies the sender is authorized (on the distribution list them selves)
 * Prepends the sender's name in the distributed message, so the recipients know
   who send the message.
 * Appends the message timestamp (if there's room), in case of SMS delivery
   delays.
 * Supports Pushover notifications. You can't reply, but at least you get a push
   notification.
 * Supports email notifications. You can't reply to these either, and they're 
   sent as your daemon/php's user, but at least it's another notification. 
   Helpful in cases where SMS delivery is failing, and for logging.
 * Commands! There are a few simple commands. Simply send your message prepended
   with the command and the output will be distributed. ex: Sending 
   `reverse hello` --> Everyone receives `olleh`
   * `reverse` -- Reverses the messge
   * `rot13` -- Performs a rot13 on the message
   * `define` -- Executes a Google Dictionary lookup and sends the result.

## Ideas / TODO ##
- [ ] Quiet hours
- [ ] Unsubscribe
- [ ] Polling/survey
- [ ] Edit your name
- [ ] Dictionary lookup should reply only to the sender if no definition was 
      found.

Most of these require a local datastore rather than being hard-coded. Not a big 
deal, just something I haven't bothered with.
