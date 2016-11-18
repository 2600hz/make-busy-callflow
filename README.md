# Kazoo Callflow tests

These tests are intended to ensure basic Kazoo functions works as expected, including REST API to manipulate Kazoo entities.


## Basic functions are (brief overview):

1. Devices are able to register and can make calls to each other and offnet resource
2. Users can call each other devices
3. Call forwarding works for user/devices
4. Call parking and retrieving works as expected
5. Users can leave and listen to voicemails
6. Ring groups works as expected, with users and devices mixed
7. Conferences works as expected: user can join as member/moderator, participant can mute/deaf himself
8. And many more, see test files

In order to run them you need to have [MakeBusy](https://github.com/2600hz/make-busy) instance working.

## How to contribute

Please see [how to](https://github.com/2600hz/make-busy/blob/master/doc/HOWTO.md) write tests for [MakeBusy](https://github.com/2600hz/make-busy),
it shouldn't be that hard.

