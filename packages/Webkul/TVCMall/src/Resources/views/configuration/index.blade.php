<x-admin::layouts>
    <x-slot:entityName>
        tvc_mall_configurations
    </x-slot>

    <x-slot:title>
        @lang('tvc_mall::app.configuration.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('tvc_mall.configuration.update', ['id' => $credential?->id])"
    >
        @method('PUT')

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('tvc_mall::app.configuration.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <button
                    type="submit"
                    class="primary-button"
                    aria-lebel="Submit"
                >
                    @lang('tvc_mall::app.configuration.edit.save')
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
                            @lang('tvc_mall::app.configuration.edit.url')
                        </x-admin::form.control-group.label >

                        <x-admin::form.control-group.control
                            type="text"
                            id="baseUrl"
                            name="baseUrl"
                            rules="required"
                            :value="$credential?->baseUrl ?? 'https://openapi.tvc-mall.com'"
                            :label="trans('tvc_mall::app.configuration.edit.url')"
                            :placeholder="trans('https://openapi.tvc-mall.com')"
                        />

                        <x-admin::form.control-group.error control-name="baseUrl" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('tvc_mall::app.configuration.edit.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="email"
                            name="email"
                            rules="required"
                            :value="old('email') ?? $credential?->email"
                            :label="trans('tvc_mall::app.configuration.edit.email')"
                            :placeholder="trans('tvc_mall::app.configuration.edit.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('tvc_mall::app.configuration.edit.password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            id="password"
                            name="password"
                            rules="required"
                            :value="old('password') ?? $credential?->password"
                            :label="trans('tvc_mall::app.configuration.edit.password')"
                            :placeholder="trans('tvc_mall::app.configuration.edit.password')"
                        />

                        <x-admin::form.control-group.error control-name="secret" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>

