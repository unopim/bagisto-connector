<x-admin::layouts.with-history>
    <x-slot:entityName>
        sunsky_attribute_mapping
    </x-slot>

    <x-slot:title>
        @lang('sunsky_online::app.mappings.attribute.title')
    </x-slot>

    <v-sunsky-attribute-mapping />

    @pushOnce('scripts')
        <script type="text/x-template" id="v-sunsky-attribute-mapping-template">
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
                            @lang('sunsky_online::app.mappings.attribute.title')
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
                                @lang('sunsky_online::app.mappings.attribute.save')
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
                        <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <div class="grid grid-cols-3 gap-10 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold">
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('sunsky_online::app.mappings.attribute.sunsky-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('sunsky_online::app.mappings.attribute.unopim-attribute')</p>
                                    <p class="break-words font-bold dark:text-slate-50 font-bold">@lang('sunsky_online::app.mappings.attribute.fixed-value')</p>
                                </div>

                                <div
                                    v-for="(sunskyAttribute, index) in standardSunskyAttributes"
                                    :key="index"
                                    class="grid grid-cols-3 gap-x-5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                                >
                                    <p :title="sunskyAttribute.title" class="break-words">@{{ sunskyAttribute.name }} [@{{ sunskyAttribute.code }}] <span class="required" v-if="sunskyAttribute.required"></span></p>

                                    <!-- UnoPim Attribute -->
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::id="'standard_attributes[' + sunskyAttribute.name + ']'"
                                            ::name="'standard_attributes[' + sunskyAttribute.name + ']'"
                                            @input="handleSelectChange($event, sunskyAttribute.code )"
                                            ::options="getAttributesByType(sunskyAttribute)"
                                            ::label="sunskyAttribute.name"
                                            ::placeholder="sunskyAttribute.name"
                                            ::value="selectMappedStandardAttribute(sunskyAttribute.code)"
                                            track-by="code"
                                            label-by="name"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes[' + sunskyAttribute.name + ']'" />
                                    </x-admin::form.control-group>

                                    <!-- Fixed Value -->
                                    <x-admin::form.control-group class="!mb-0 flex gap-4  ">
                                        <x-admin::form.control-group.control
                                            type="text"
                                            ::id="'standard_attributes_default[' + sunskyAttribute.code + ']'"
                                            ::name="'standard_attributes_default[' + sunskyAttribute.code + ']'"
                                            ::value="selectMappedStandardAttributeDefault(sunskyAttribute.code)"
                                            ::label="sunskyAttribute.name"
                                            ::placeholder="sunskyAttribute.name"
                                        />

                                        <x-admin::form.control-group.error ::control-name="'standard_attributes_default[' + sunskyAttribute.code + ']'" />
                                         <!-- Remove Field Button (only for merged fields) -->
                                        <span v-if="!sunskyAttribute.id" class="flex justify-end">
                                            <button
                                                type="button"
                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                @click="removeAttribute(index)"
                                            >
                                            </button>
                                        </span>
                                    </x-admin::form.control-group>
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
                    @lang('sunsky_online::app.mappings.attribute.additional-attributes.title')
                </p>
                <span class="text-xs text-gray-500">@lang('sunsky_online::app.mappings.attribute.additional-attributes.description')</span>

                <div>
                    <div class="flex items-center justify-start gap-4">
                        <x-admin::form.control-group class="!mb-0 w-full">
                            <x-admin::form.control-group.control
                                type="text"
                                id="newSunskyAttributes"
                                name="newSunskyAttributes"
                                v-model="newSunskyAttributes"
                                label="Sunsky Attribute Code"
                                value=""
                                :placeholder="trans('sunsky_online::app.mappings.attribute.sunsky-attribute')"
                            />

                            <x-admin::form.control-group.error control-name="newSunskyAttributes" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0 w-full">
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
                                :placeholder="trans('sunsky_online::app.mappings.attribute.sunsky-attribute')"
                                :options="$attributeTypesJson"
                                track-by="id"
                                label-by="label"
                            >
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="type" />
                        </x-admin::form.control-group>
                        <span
                            class="primary-button cursor-pointer"
                            @click="addSunskyAttribute"
                        >
                            @lang('sunsky_online::app.mappings.attribute.add')
                        </span>
                        <div class="mb-4 !mb-0 w-full"></div>

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
                        newSunskyAttributes: '',
                        attributeType: '',
                        mappedAdditionalAttributes: @json($additionalAttributes),
                    };
                },


                methods: {
                    addSunskyAttribute() {
                        if (!this.newSunskyAttributes || !this.attributeType) {
                            this.$emitter.emit('add-flash', {
                                type: 'warning',
                                message: @json(__('sunsky_online::app.mappings.attribute.attribute_or_type_missing'))
                            });
                            return;
                        }

                        const newAttribute = {
                            name: this.newSunskyAttributes,
                            code: this.newSunskyAttributes,
                            type: JSON.parse(this.attributeType).id
                        };

                        this.$emit('add-attribute', newAttribute);
                        this.mappedAdditionalAttributes.push(newAttribute);

                        this.$axios.post("{{ route('sunsky_online.attributes.add_attributes') }}", newAttribute)
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message
                                });
                                this.newSunskyAttributes = '';
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
            app.component('v-sunsky-attribute-mapping', {
                template: '#v-sunsky-attribute-mapping-template',
                data() {

                    return {
                        isLoading: false,
                        sunskyAttributes: @json($sunskyAttributes),
                        attributes: @json($attributes),
                        standardAttributes: Object.assign({}, @json($standardAttributes?->mapped_value)),
                        standardAttributesDefaults: Object.assign({}, @json($standardAttributes?->fixed_value)),
                        additionalAttributes: [],
                        standardSunskyAttributes: @json($sunskyAttributes),
                        mappedAdditionalAttributes: @json($additionalAttributes)
                    };
                },

                mounted() {
                    console.log('standard', this.standardSunskyAttributes);
                    this.standardSunskyAttributes.push(...this.mappedAdditionalAttributes);
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

                    getAttributesByType(sunskyAttribute) {
console.log('sunskyAttribute', sunskyAttribute.code, sunskyAttribute.type, sunskyAttribute.unique);
                        return this.attributes.filter(attribute => {
                            if (sunskyAttribute.unique) {
                                return attribute.is_unique && attribute.type === sunskyAttribute.type;
                            }
                            if (attribute.code == 'gmtModified' & sunskyAttribute.code == 'gmtModified') {
                                console.log('attribute, sunskyAttribute', attribute, sunskyAttribute );
                            }
                            return attribute.type === sunskyAttribute.type;
                        });
                    },

                    addAdditionalAttribute(newAttribute) {
                        if (newAttribute) {
                            this.additionalAttributes.push(newAttribute);
                            this.standardSunskyAttributes.push(newAttribute);
                        }
                    },

                    removeAttribute(index) {
                        this.$emitter.emit('open-delete-modal', {
                            agree: () => {
                                let attributeCode = {
                                    code: this.standardSunskyAttributes[index].code
                                };
                                this.$axios.post("{{ route('sunsky_online.attributes.remove_attributes') }}",
                                    attributeCode);
                                let removedItem = this.standardSunskyAttributes.splice(index, 1);
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

                        this.$axios.post("{{ route('sunsky_online.mappings.attributes.store_or_update') }}", formData)
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
