<template>
    <canvas ref="$canvas">
        <slot>
            <chart-alt-values
                v-if="alt.length > 0"
                :alt="alt"
            />
        </slot>
    </canvas>
</template>

<script setup lang="ts">
import {useTranslate} from "~/vendor/gettext";
import {ref} from "vue";
import ChartAltValues from "~/components/Common/Charts/ChartAltValues.vue";
import useChart, {chartProps} from "~/functions/useChart";

const props = defineProps({
    ...chartProps,
    labels: {
        type: Array,
        default: () => {
            return [];
        }
    }
});

const $canvas = ref(); // Template Ref
const {$gettext} = useTranslate();

useChart(
    props,
    $canvas,
    {
        type: 'bar',
        options: {
            aspectRatio: 2,
            plugins: {
                autocolors: {
                    enabled: true
                }
            },
            scales: {
                x: {
                    scaleLabel: {
                        display: true,
                        labelString: $gettext('Hour')
                    }
                },
                y: {
                    scaleLabel: {
                        display: true,
                        labelString: $gettext('Listeners')
                    },
                    ticks: {
                        min: 0
                    }
                }
            }
        }
    }
);
</script>
