<x-admin::layouts>
    <x-slot:title>
        @lang('tvc_mall::app.mapping.product.title')
    </x-slot>

    {!! view_render_event('unopim.admin.tvcmall.mapping.product.attribute.before') !!}

    <v-attributes>
        <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('tvc_mall::app.mapping.product.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                @if (bouncer()->hasPermission('tvc_mall.product_mapping.create'))
                    <button type="button" class="primary-button" @click="$refs.createUpdateModal.toggle()">
                        @lang('tvc_mall::app.mapping.product.datagrid.create-btn')
                    </button>
                @endif
            </div>
        </div>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-attributes>

    {!! view_render_event('unopim.admin.tvcmall.mapping.product.attribute.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-attributes-template">
            <div class="flex  gap-4 justify-between items-center max-sm:flex-wrap">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('tvc_mall::app.mapping.product.title')
                </p>
    
                <div class="flex gap-x-2.5 items-center">
                    @if (bouncer()->hasPermission('tvc_mall.product_mapping.create'))
                        <button type="button" class="primary-button" @click="$refs.createUpdateModal.toggle()">
                            @lang('tvc_mall::app.mapping.product.datagrid.create-btn')
                        </button>
                    @endif
                </div>
            </div>

            <x-admin::datagrid
                :src="route('tvc_mall.product-attribute-mapping.index')"
                ref="datagrid"
            >
                <!-- DataGrid Body -->
                <template #body="{ columns, records, performAction, setCurrentSelectionMode, applied }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >
                        <!-- Mass actions -->
                        @if (
                            bouncer()->hasPermission('tvc_mall.product_mapping.delete')
                        )
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.id}`"
                                :id="`mass_action_select_record_${record.id}`"
                                :value="record.id"
                                class="hidden peer"
                                v-model="applied.massActions.indices"
                                @change="setCurrentSelectionMode"
                            >

                            <label
                                class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"
                                :for="`mass_action_select_record_${record.id}`"
                            ></label>
                        @endif

                        <!-- id -->
                        <p v-html="record.id"></p>

                        <!-- code -->
                        <p v-html="record.unopim_code"></p>

                        <!-- name -->
                        <p v-html="record.tvc_mall_code"></p>

                        <!-- Actions -->
                        <div class="flex justify-end">
                            @if (bouncer()->hasPermission('tvc_mall.product_mapping.delete'))
                                <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                    <span
                                        :class="record.actions.find(action => action.index === 'delete')?.icon"
                                        title="@lang('admin::app.settings.currencies.index.datagrid.edit')"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                    >
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>
            
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form @submit="handleSubmit($event, create)" ref="createUpdateForm">
                    <!-- Create Update Modal -->
                    <x-admin::modal ref="createUpdateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p
                                class="text-lg text-gray-800 dark:text-white font-bold"
                            >
                                @lang('tvc_mall::app.mapping.product.datagrid.create-title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('tvc_mall::app.mapping.product.datagrid.unopim-code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="unopim_code"
                                        rules="required"
                                        :label="trans('tvc_mall::app.mapping.product.datagrid.unopim-code')"
                                        :options="json_encode($unopim_codes)"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="unopim_code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('tvc_mall::app.mapping.product.datagrid.tvc-mall-code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="tvc_mall_code"
                                        rules="required"
                                        :label="trans('tvc_mall::app.mapping.product.datagrid.tvc-mall-code')"
                                        :options="json_encode($tvc_mall_codes)"
                                        track-by="id"
                                        label-by="label"
                                    >
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="tvc_mall_code" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <!-- Modal Submission -->
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('tvc_mall::app.mapping.product.datagrid.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-attributes', {
                template: '#v-attributes-template',

                data() {
                    return {};
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    create(params, {
                        resetForm,
                        setErrors
                    }) {
                        let formData = new FormData(this.$refs.createUpdateForm);

                        this.$axios.post("{{ route('tvc_mall.product-attribute-mapping.store') }}", formData)
                            .then((response) => {
                                this.$refs.createUpdateModal.close();

                                this.$refs.datagrid.get();

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });

                                resetForm();
                            })
                            .catch(error => {
                                if (error?.response?.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    editModal(url) {
                        // this.codeIsNew = false;

                        // this.$axios.get(url)
                        //     .then((response) => {
                        //         this.selectedCurrency = response.data;

                        //         this.$refs.currencyUpdateOrCreateModal.toggle();
                        //     })
                        //     .catch(error => {
                        //         this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message })
                        //     });
                    },
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
