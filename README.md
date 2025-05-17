Laravel Builder.io
==================

Laravel package for Builder.io integration for rendering content on your own, instead of using Builder.io API and CDN.

While it is completely possible to leverage just Visual Editor of the Builder.io and render content without relying on Builder.io API,
the functionality is limited. When using Builder.io API, they take care of targeting, localization and utilization of other features such as
symbols, content data etc. When rendering content on your own, you have to take care of targeting, symbol resolution and content preparation for rendering.

This package helps a with retrieving content from Builder.io and storing it in the database for later rendering directly by just reading
the database and not querying Builder.io API on each page render.

### Features

- store Builder.io content in the database
- automatically update database content as soon as something changes in Builder.io, utilizing Builder.io global webhooks
- download image and video assets to Laravel Storage without need to use CDN
- Symbol support when retrieving content

### Installation

To install the package, just run:

```
composer require stacktrace/laravel-builder
```

After installing, configure following environment variables:

```dotenv
# The Builder.io Public API key, can be found in settings
BUILDER_PUBLIC_API_KEY=123
# The Builder.io Private API key, can be found in settings as well
BUILDER_PRIVATE_KEY=123
# Random string for protecting webhook calls
BUILDER_WEBHOOK_TOKEN=somerandomstring
```

Then on Builder.io side, you have to configure webhook. 
Open Space Settings and within Integrations section, click on "Edit" next to Global Webhooks.
Register the following webhook:
```
URL: https://your-laravel-app.com/__builder_webhook__
Headers:
    Authorization: somerandomstring
```

Set the Authorization header value to the `BUILDER_WEBHOOK_TOKEN` env variable.

### Usage

#### Rendering pages

To render a page, first you have to prepare a controller. The package supports simple path targeting, so you can use single path target on your pages.

First, create controller for resolving content from current request:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use StackTrace\Builder\Facades\Builder;

class BuilderPageController extends Controller
{
    public function __invoke(Request $request)
    {
        $page = Builder::resolvePageFromRequest($request);

        abort_if(is_null($page), 404);

        return Inertia::render('BuilderPage', [
            'title' => $page->title,
            'content' => $page->getContent(),
        ]);
    }
}
```

Then in `web.php` register this controller as fallback controller, since we want to dynamically render page based on path targeting:

```php
Route::fallback(BuilderPageController::class);
```

In the Builder.io, set targeting of your page to something like `urlPath is /test`. Provided that you do not have a route registered
on the `/test` path, the fallback controller will engage, and `Builder::resolvePageFromRequest` will search for a page on `/test` path.

You can now render the content directly using Builder.io SDK like following:

```vue
<template>
  <Head :title="title"/>

  <Content :content="content" api-key="@" :can-track="false" />
</template>

<script setup lang="ts">
import { Content } from "@builder.io/sdk-vue";
import { Head } from "@inertiajs/vue3";

defineProps<{
  content: object
  title: string
}>()
</script>
```

#### Builder editor

To be able to edit pages, within Builder.io on your site, create separate controller for handling editor requests:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use StackTrace\Builder\Facades\Builder;

class BuilderEditorController extends Controller
{
    public function __invoke(Request $request)
    {
        $editor = Builder::resolveEditorFromRequest($request);

        abort_if(is_null($editor), 404);

        App::setLocale($editor->locale);

        return Inertia::render('BuilderEditorPage', [
            'apiKey' => $editor->apiKey,
            'model' => $editor->model,
            'url' => $editor->url,
        ]);
    }
}

```

Register controller in `web.php`:

```php
Route::get('__builder_editing__', BuilderEditorController::class);
```

Then render editor content using Builder.io SDK:

```vue
<template>
  <Content
      v-if="content"
      :content="content"
      :api-key="apiKey"
      :model="model"
      :can-track="false"
  />
</template>

<script setup lang="ts">
  import { Content, fetchOneEntry } from '@builder.io/sdk-vue'
  import { onMounted, shallowRef } from "vue";

  const props = defineProps<{
    apiKey: string
    model: string
    url: string
  }>()

  const content = shallowRef<any>(null)

  onMounted(async () => {
    content.value = await fetchOneEntry({
      model: props.model,
      apiKey: props.apiKey,
      userAttributes: {
        urlPath: props.url,
      }
    })
  })
</script>
```

Note that we now we are using `fetchOneEntry` to render content using Builder.io API. This is however necessary for editor to work.

Then in your model settings, use a dynamic editor URL. Enter the following URL:

```js
return `https://your-laravel-app.com/__builder_editing__?path=${targeting.urlPath || ''}&locale=${content.data.locale || ''}&model=${content.modelId}`;
```

The editor will now use your app instead of Builder.io preview page.

#### Localization

While localization is a premium feature in Builder.io, you can use simple field to store locale of your content. Just define a field in your model
settings named `locale`. When receiving webhooks, the package looks for fields and if it finds a field named `locale`, it will store it in `local` column.
The `Builder::resolveEditorFromRequest` will respect locale settings with following rules:

- first retrieve content where `locale` is matching `App:getLocale()`
- if the `locale` is not matching `App::getLocale()`, the content where `locale` matches `App::getFallbackLocale()` is retrieved
- if content fallback locale does not exist either, the content where `locale` is not set is retrieved
