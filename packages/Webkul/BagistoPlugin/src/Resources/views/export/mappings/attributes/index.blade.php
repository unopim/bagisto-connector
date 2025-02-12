<x-admin::layouts.with-history>
    <x-slot:entityName>
        bagitsto_attribute_mapping
    </x-slot>

    <x-slot:title>
        @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.title')
    </x-slot>

    <v-attribute-mapping 
        :bagisto-attributes='@json($bagistoAttributes)'
        :attributes='@json($attributes)'
    /> 

    @pushOnce('scripts')
        <script type="text/x-template" id="v-attribute-mapping-template">
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="storeAttributeMapping"
            >
                <form
                    @submit="handleSubmit($event, storeAttributeMapping)"
                    ref="storeAttributeMappingForm"
                >
                    <div class="flex justify-between items-center">
                        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                            @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.title')
                        </p>

                        <div class="flex gap-x-2.5 items-center">
                            <!-- Save Button -->
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                <!-- Spinner -->
                                <svg v-if="isLoading" class="align-center inline-block animate-spin h-5 w-5 ml-2 text-white-700" xmlns="http://www.w3.org/2000/svg" fill="none"  aria-hidden="true" viewBox="0 0 24 24">
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    >
                                    </circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    >
                                    </path>
                                </svg>
                                @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.save')
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <div class="grid grid-cols-3 gap-10 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold">
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.bagisto-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.unopim-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.fixed-value')</p>
                                </div>

                                <div
                                    v-for="(bagistoAttribute, index) in standardBagistoAttributes"
                                    :key="index"
                                    class="grid grid-cols-3 gap-x-5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                >
                                    <p :title="bagistoAttribute.title" class="break-words">@{{ bagistoAttribute.name }} [@{{ bagistoAttribute.code }}] <span class="required" v-if="bagistoAttribute.required"></span></p>

                                    <!-- UnoPim Attribute -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::id="'standard_attributes[' + bagistoAttribute.name + ']'"
                                            ::name="'standard_attributes[' + bagistoAttribute.name + ']'"
                                            @input="handleSelectChange($event, bagistoAttribute.code )"
                                            ::options="getAttributesByType(bagistoAttribute)"
                                            ::label="bagistoAttribute.name"
                                            ::placeholder="bagistoAttribute.name"
                                            ::value="selectMappedStandardAttribute(bagistoAttribute.code)"
                                            track-by="code"
                                            label-by="name"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes[' + bagistoAttribute.name + ']'" />
                                    </x-admin::form.control-group>

                                    <!-- Fixed Value -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            ::id="'standard_attributes_default[' + bagistoAttribute.code + ']'"
                                            ::name="'standard_attributes_default[' + bagistoAttribute.code + ']'"
                                            ::value="selectMappedStandardAttributeDefault(bagistoAttribute.code)"
                                            ::label="bagistoAttribute.name"
                                            ::placeholder="bagistoAttribute.name"
                                            ::disabled="isDisabled(bagistoAttribute.code)"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes_default[' + bagistoAttribute.code + ']'" />
                                    </x-admin::form.control-group>

                                    <!-- Remove Field Button (only for merged fields) -->
                                    <span v-if="!bagistoAttribute.id">
                                        <button
                                            type="button"
                                            class="text-red-500 hover:text-red-700"
                                            @click="removeAttribute(index)"
                                        >
                                            @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.remove')
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <v-additional-attribute-mapping
                                :attributes="attributes"
                                @add-attribute="addAdditionalAttribute"
                                @remove-attribute="removeAdditionalAttribute"
                            />
                        </div>
                    </div>
                </form>
            </x-admin::form>
        </script>

        <script type="text/x-template" id="v-additional-attribute-mapping-template">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                    @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.additional-attributes.title')
                </p>
                <span class="text-xs text-gray-500">@lang('bagisto_plugin::app.bagisto-plugin.export.mapping.additional-attributes.description')</span>
                
                <div>
                    <div class="flex items-center justify-start gap-4">
                        <x-admin::form.control-group class="!mb-0 w-[320px]">
                            <x-admin::form.control-group.control
                                type="text"
                                id="newBagistoAttributes"
                                name="newBagistoAttributes"
                                v-model="newBagistoAttributes"
                                label="Bagisto Attribute Code"
                                value=""
                                placeholder="Bagisto Attribute Code"
                            />

                            <x-admin::form.control-group.error control-name="newBagistoAttributes" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0 w-[200px]">
                        @php
                            $supportedTypes = ['text', 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'gallery', 'file', 'checkbox'];

                            $attributeTypes = [];

                            foreach($supportedTypes as $type) {
                                $attributeTypes[] = [
                                    'id'    => $type,
                                    'label' => trans('admin::app.catalog.attributes.create.'. $type)
                                ];
                            }

                            $attributeTypesJson = json_encode($attributeTypes);

                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="type"
                            class="cursor-pointer"
                            name="type"
                            :value="old('type')"
                            v-model="attributeType"
                            :label="trans('admin::app.catalog.attributes.create.type')"
                            placeholder="Select Attribute Type"
                            :options="$attributeTypesJson"
                            track-by="id"
                            label-by="label"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="type" />
                    </x-admin::form.control-group>
                        <span
                            class="primary-button cursor-pointer"
                            @click="addBagistoAttribute"
                        >
                            @lang('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.add')
                        </span>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-additional-attribute-mapping', {
                template: '#v-additional-attribute-mapping-template',
                props: ['attributes'],
                data() {
                    return {
                        newBagistoAttributes: '',
                        attributeType: '',
                        mappedAdditionalAttributes: @json($additionalAttributes),
                    };
                },
                methods: {
                    addBagistoAttribute() {
                        if (!this.newBagistoAttributes || !this.attributeType) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: @json(__('bagisto_plugin::app.bagisto-plugin.export.mapping.attributes.flash-message'))
                            });
                            return;
                        }

                        const newAttribute = {
                            name: this.newBagistoAttributes,
                            code: this.newBagistoAttributes,
                            type: JSON.parse(this.attributeType).id
                        };

                        this.$emit('add-attribute', newAttribute);
                        this.mappedAdditionalAttributes.push(newAttribute);

                        this.$axios.post("{{ route('admin.bagisto_plugin.attributes.add') }}", newAttribute)
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });
                                this.newBagistoAttributes = '';
                                this.attributeType = '';
                            })
                            .catch(error => {
                                console.error(error);
                                this.mappedAdditionalAttributes.pop();
                            });
                    },
                }
            });
        </script>

        <script type="module">
            app.component('v-attribute-mapping', {
                template: '#v-attribute-mapping-template',
                props: ['bagistoAttributes', 'attributes'],
                data() {
                    return {
                        isLoading: false,
                        standardAttributes: Object.assign({}, @json($standardAttributes?->mapped_value)),
                        standardAttributesDefaults: Object.assign({}, @json($standardAttributes?->fixed_value)),
                        additionalAttributes: [],
                        standardBagistoAttributes: this.bagistoAttributes,
                        mappedAdditionalAttributes: @json($additionalAttributes)
                    };
                },
                
                mounted() {
                    this.standardBagistoAttributes.push(...this.mappedAdditionalAttributes);
                },

                methods: {
                    handleSelectChange(value, fieldCode) {
                        try {
                            if (value) {
                                let selectedValue = JSON.parse(value);
                                this.standardAttributes[fieldCode] = selectedValue.code;
                            } else {
                                delete this.standardAttributes[fieldCode];
                            }
                        } catch (e) {}
                    },

                    isDisabled(code) {
                        return this.standardAttributes[code] ? true : false;
                    },

                    selectMappedStandardAttribute(fieldCode) {
                        return this.standardAttributes[fieldCode] ?? null;
                    },

                    selectMappedStandardAttributeDefault(fieldCode) {
                        return this.standardAttributesDefaults[fieldCode] ?? null;
                    },

                    getAttributesByType(bagistoAttribute) {
                        return this.attributes.filter(attribute => {
                            if (bagistoAttribute.unique) {
                                return attribute.is_unique && attribute.type === bagistoAttribute.type;
                            }
                            return attribute.type === bagistoAttribute.type;
                        });
                    },

                    addAdditionalAttribute(newAttribute) {
                        if (newAttribute) {
                            this.additionalAttributes.push(newAttribute);
                            this.standardBagistoAttributes.push(newAttribute);
                        }
                    },

                    removeAttribute(index) {
                        this.$emitter.emit('open-delete-modal', {
                            agree: () => {
                                let attributeCode = {
                                    code: this.standardBagistoAttributes[index].code
                                };
                                this.$axios.post("{{ route('admin.bagisto_plugin.attributes.remove') }}",
                                    attributeCode);
                                let removedItem = this.standardBagistoAttributes.splice(index, 1);
                            }
                        });
                    },

                    storeAttributeMapping(params, {
                        resetForm,
                        setErrors
                    }) {
                        this.isLoading = true;

                        let formData = new FormData(this.$refs.storeAttributeMappingForm);

                        formData.append('standard_attributes', JSON.stringify(this.standardAttributes));

                        this.$axios.post("{{ route('admin.bagisto_plugin.mappings.attributes.store') }}", formData)
                            .then((response) => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });
                            })
                            .catch(error => {
                                if (error.status == 400) {
                                    setErrors(error.response.data.errors);
                                }
                            }).then(() => {
                                this.isLoading = false;
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
