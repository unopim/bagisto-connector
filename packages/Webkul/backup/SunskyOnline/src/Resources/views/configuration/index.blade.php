<x-admin::layouts>
    <x-slot:entityName>
        sunsky_online_configurations
    </x-slot>

    <x-slot:title>
        @lang('sunsky_online::app.configuration.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('sunsky_online.configuration.update', ['id' => $credential?->id])"
    >
        @method('PUT')

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('sunsky_online::app.configuration.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <button
                    type="submit"
                    class="primary-button"
                    aria-lebel="Submit"
                >
                    @lang('sunsky_online::app.configuration.edit.save')
                </button>
            </div>
        </div>

        <!-- body content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- Configuration -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.channels.edit.general')
                    </p>

                    <!-- baseUrl -->
                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('sunsky_online::app.configuration.edit.url')
                        </x-admin::form.control-group.label >

                        <x-admin::form.control-group.control
                            type="text"
                            id="baseUrl"
                            name="baseUrl"
                            rules="required"
                            :value="'https://open.sunsky-online.com' ?? $credential?->baseUrl"
                            :label="trans('sunsky_online::app.configuration.edit.url')"
                            :placeholder="trans('https://open.sunsky-online.com')"
                        />

                        <x-admin::form.control-group.error control-name="baseUrl" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('sunsky_online::app.configuration.edit.key')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="key"
                            name="key"
                            rules="required"
                            :value="old('key') ?? $credential?->key"
                            :label="trans('sunsky_online::app.configuration.edit.key')"
                            :placeholder="trans('sunsky_online::app.configuration.edit.key')"
                        />

                        <x-admin::form.control-group.error control-name="key" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('sunsky_online::app.configuration.edit.secret')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            id="secret"
                            name="secret"
                            rules="required"
                            :value="old('secret') ?? $credential?->secret"
                            :label="trans('sunsky_online::app.configuration.edit.secret')"
                            :placeholder="trans('sunsky_online::app.configuration.edit.secret')"
                        />

                        <x-admin::form.control-group.error control-name="secret" />
                    </x-admin::form.control-group>
                </div>

                <!-- Locales Mapping -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('sunsky_online::app.configuration.edit.locales-mapping')
                    </p>

                    <x-admin::form.control-group  >
                        <div class="flex flex-row gap-4 items-between justify-between font-extrabold">
                            <p class=" text-gray-600 dark:text-white  mb-4 w-full">
                                @lang('sunsky_online::app.configuration.edit.unopim-locale')
                            </p>
                            <p class=" text-gray-600 dark:text-white  mb-4 w-full">
                                @lang('sunsky_online::app.configuration.edit.sunsky-language')
                            </p>
                        </div>

                        @foreach (core()->getAllActiveLocales() as $locale)
                            <x-admin::form.control-group class="flex flex-row gap-4 items-between justify-between">
                                    <x-admin::form.control-group.label class="text-gray-600  required w-full">
                                        {{  $locale->name . ' - ' . $locale->code }}
                                    </x-admin::form.control-group.label >
                                    @php
                                        $code = $locale->code;
                                        $additional = json_decode($credential?->additional, true);
                                        $localesMapping = $additional['localesMapping'] ?? [];
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="locale_mapping-{{$locale->code}}"
                                        name="locale_mapping-{{$locale->code}}"
                                        rules="required"
                                        :placeholder="trans('sunsky_online::app.configuration.edit.select-sunsky-language')"
                                        :options="$sunskyLanguages"
                                        :value="old('locale_mapping-' . $locale->code) ?: $localesMapping[$locale->code] ?? null"
                                        track-by="id"
                                        label-by="name"
                                        class="w-full"
                                    />
                                    <x-admin::form.control-group.error control-name="sunsky-language" />
                            </x-admin::form.control-group>
                        @endforeach
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
