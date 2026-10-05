<?php

it('returns the shared error envelope for unknown routes', function () {
    assertApiError($this->getJson('/api/v1/does-not-exist'), 404, 'not_found');
});

it('returns the shared error envelope for wrong methods', function () {
    assertApiError($this->deleteJson('/api/v1/sections'), 405, 'http_error');
});
