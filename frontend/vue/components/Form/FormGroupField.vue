<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template
            v-if="label || slots.label"
            #label="slotProps"
        >
            <form-label
                :is-required="isRequired"
                :advanced="advanced"
            >
                <slot
                    name="label"
                    v-bind="slotProps"
                >
                    {{ label }}
                </slot>
            </form-label>
        </template>

        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, class: fieldClass }"
            >
                <textarea
                    v-if="inputType === 'textarea'"
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="filteredModel"
                    :name="name"
                    :required="isRequired"
                    :autofocus="autofocus"
                    class="form-control"
                    :class="fieldClass"
                />
                <input
                    v-else
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="filteredModel"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    :autofocus="autofocus"
                    class="form-control"
                    :class="fieldClass"
                >
            </slot>

            <vuelidate-error
                v-if="isVuelidateField"
                :field="field"
            />
        </template>

        <template
            v-if="description || slots.description"
            #description="slotProps"
        >
            <slot
                v-bind="slotProps"
                name="description"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts">
import VuelidateError from "./VuelidateError.vue";
import {computed, ref, useSlots} from "vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormLabel from "~/components/Form/FormLabel.vue";
import {formFieldProps, useFormField} from "~/components/Form/useFormField";

const props = defineProps({
    ...formFieldProps,
    id: {
        type: String,
        required: true
    },
    name: {
        type: String,
        default: null
    },
    label: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    },
    inputType: {
        type: String,
        default: 'text'
    },
    inputNumber: {
        type: Boolean,
        default: false
    },
    inputTrim: {
        type: Boolean,
        default: false
    },
    inputEmptyIsNull: {
        type: Boolean,
        default: false
    },
    inputAttrs: {
        type: Object,
        default() {
            return {};
        }
    },
    autofocus: {
        type: Boolean,
        default: false
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const slots = useSlots();

const emit = defineEmits(['update:modelValue']);

const {model, isVuelidateField, fieldClass, isRequired} = useFormField(props, emit);

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number" || props.inputType === "range";
});

const filteredModel = computed({
    get() {
        return model.value;
    },
    set(newValue) {
        if ((isNumeric.value || props.inputEmptyIsNull) && '' === newValue) {
            newValue = null;
        }

        if (props.inputTrim && null !== newValue) {
            newValue = newValue.replace(/^\s+|\s+$/gm, '');
        }

        if (isNumeric.value) {
            newValue = Number(newValue);
        }

        model.value = newValue;
    }
});

const $input = ref(); // Input

const focus = () => {
    $input.value?.focus();
};

defineExpose({
    focus
});
</script>
