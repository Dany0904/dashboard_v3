/* eslint-disable no-console */

define(["jquery", "core/ajax", "local_dashboard_v3/apexcharts"], function ($, Ajax, ApexCharts) {
  return {
    init: function () {

      const urlParams = new URLSearchParams(window.location.search);

      let days = parseInt(urlParams.get("days")) || 30;
      let courseid = parseInt(urlParams.get("courseid")) || 0;

      // =========================
      // Filtros
      // =========================
      const filterDays = document.getElementById("filter-days");
      const filterCourse = document.getElementById("filter-course");

      if (filterDays) {
        filterDays.addEventListener("change", function () {
          const url = new URL(window.location.href);
          url.searchParams.set("days", this.value);
          window.location.href = url.toString();
        });
      }

      if (filterCourse) {
        filterCourse.addEventListener("change", function () {
          const url = new URL(window.location.href);
          url.searchParams.set("courseid", this.value);
          window.location.href = url.toString();
        });
      }

      // =========================
      // AJAX (MULTIPLE REQUESTS)
      // =========================
      const requests = Ajax.call([
        {
          methodname: "local_dashboard_v3_get_user_activity_chart",
          args: {
            days: days,
            courseid: courseid
          }
        },
        {
          methodname: "local_dashboard_v3_get_user_segmentation",
          args: {
            days: days,
            courseid: courseid
          }
        }
      ]);

      $.when(requests[0], requests[1]).done(function (activityRes, segmentationRes) {

        // ======================================
        // GRÁFICA 1: ACTIVIDAD USUARIOS
        // ======================================
        let labels = [];
        let currentData = [];
        let previousData = [];

        activityRes.current.forEach(item => {
          labels.push(item.label);
          currentData.push(item.value);
        });

        activityRes.previous.forEach(item => {
          previousData.push(item.value);
        });

        if (
          window.activityChartInstance &&
          typeof window.activityChartInstance.destroy === "function"
        ) {
          window.activityChartInstance.destroy();
        }

        const activityOptions = {
          chart: {
            type: "line",
            height: 350
          },
          series: [
            {
              name: "Periodo actual",
              data: currentData
            },
            {
              name: "Periodo anterior",
              data: previousData
            }
          ],
          xaxis: {
            categories: labels
          },
          stroke: {
            curve: "smooth",
            width: [3, 2],
            dashArray: [0, 5]
          },
          colors: ["#4e73df", "#858796"],
          tooltip: {
            y: {
              formatter: val => val + " usuarios"
            }
          }
        };

        window.activityChartInstance = new ApexCharts(
          document.querySelector("#activityChart"),
          activityOptions
        );

        window.activityChartInstance.render();

        // ======================================
        // GRÁFICA 2: SEGMENTACIÓN (DONUT)
        // ======================================
        const series = [
          segmentationRes.active,
          segmentationRes.recurrent,
          segmentationRes.new,
          segmentationRes.inactive
        ];

        const labelsDonut = [
          "Activos",
          "Recurrentes",
          "Nuevos",
          "Inactivos"
        ];

        if (
          window.userSegmentationChart &&
          typeof window.userSegmentationChart.destroy === "function"
        ) {
          window.userSegmentationChart.destroy();
        }

        const donutOptions = {
          chart: {
            type: "donut",
            height: 350
          },
          series: series,
          labels: labelsDonut,
          colors: ["#1cc88a", "#36b9cc", "#4e73df", "#e74a3b"],
          legend: {
            position: "bottom"
          },
          dataLabels: {
            enabled: true,
            formatter: function (val) {
              return val.toFixed(1) + "%";
            }
          },
          tooltip: {
            y: {
              formatter: function (val) {
                return val + " usuarios";
              }
            }
          }
        };

        window.userSegmentationChart = new ApexCharts(
          document.querySelector("#userSegmentationChart"),
          donutOptions
        );

        window.userSegmentationChart.render();

      }).fail(function (err) {
        console.error(err);
      });
    }
  };
});