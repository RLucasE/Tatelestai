#!/bin/bash

# Start backend server
(cd ./Backend/example-app && php artisan serve) &

# Start frontend dev server
(cd ./Frontend/vue-project && npm run dev)

