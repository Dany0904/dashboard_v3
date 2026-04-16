/* eslint-disable no-console */

define([
  "jquery",
  "core/ajax",
  "local_dashboard_v3/apexcharts"
], function ($, Ajax, ApexCharts) {
  return {
    init: function () {

      // =========================
      // PARAMS URL
      // =========================
      const urlParams = new URLSearchParams(window.location.search);

      let days = parseInt(urlParams.get("days")) || 30;

      // =========================
      // FILTRO DÍAS
      // =========================
      const filterDays = document.getElementById("filter-days");

      if (filterDays) {
        filterDays.addEventListener("change", function () {
          const url = new URL(window.location.href);
          url.searchParams.set("days", this.value);
          window.location.href = url.toString();
        });
      }

      // =========================
      // AJAX REQUESTS
      // =========================
      const requests = Ajax.call([
        {
          methodname: "local_dashboard_v3_get_teacher_activity_chart"
          ,
          args: { days: days }
        },
        {
            methodname: "local_dashboard_v3_get_teacher_active_daily",
            args: { days: days }
        },
        {
            methodname: "local_dashboard_v3_get_teacher_course_intervention",
            args: { days: days, limit: 10 }
        }
      ]);

     $.when(
            requests[0],
            requests[1],
            requests[2]
            ).done(function (activityRes, activityDailyRes, interventionRes) {

          // =========================
          // ACTIVIDAD DOCENTE (LÍNEA)
          // =========================
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

          // destruir instancia previa
          if (
            window.teacherActivityChart &&
            typeof window.teacherActivityChart.destroy === "function"
          ) {
            window.teacherActivityChart.destroy();
          }

          const options = {
            chart: {
              type: "line",
              height: 360,
              toolbar: { show: false }
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
            stroke: {
              curve: "smooth",
              width: [3, 2],
              dashArray: [0, 5]
            },
            xaxis: {
              categories: labels
            },
            colors: ["#4e73df", "#858796"],
            dataLabels: {
              enabled: false
            },
            legend: {
              position: "top"
            },
            tooltip: {
              shared: true,
              intersect: false
            },
            grid: {
              borderColor: "#e9ecef"
            }
          };

          window.teacherActivityChart = new ApexCharts(
            document.querySelector("#teacherActivityChart"),
            options
          );

          window.teacherActivityChart.render();

          //////////////////////

          let labels2 = [];
            let data2 = [];

            activityDailyRes.data.forEach(item => {
            labels2.push(item.label);
            data2.push(item.value);
            });

            if (
            window.teacherActiveDailyChart &&
            typeof window.teacherActiveDailyChart.destroy === "function"
            ) {
            window.teacherActiveDailyChart.destroy();
            }

            const options2 = {
            chart: {
                type: "bar",
                height: 350,
                toolbar: { show: false }
            },
            series: [
                {
                name: "Docentes activos",
                data: data2
                }
            ],
            xaxis: {
                categories: labels2
            },
            colors: ["#36b9cc"],
            plotOptions: {
                bar: {
                borderRadius: 4,
                columnWidth: "55%"
                }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                formatter: val => val + " docentes"
                }
            }
            };

            window.teacherActiveDailyChart = new ApexCharts(
            document.querySelector("#teacherActiveDailyChart"),
            options2
            );

            window.teacherActiveDailyChart.render();

            ///////////////////

            let labels3 = [];
            let data3 = [];

            interventionRes.data.forEach(item => {
            labels3.push(item.label);
            data3.push(item.value);
            });

            if (
            window.teacherCourseInterventionChart &&
            typeof window.teacherCourseInterventionChart.destroy === "function"
            ) {
            window.teacherCourseInterventionChart.destroy();
            }

            const options3 = {
            chart: {
                type: "bar",
                height: 380,
                toolbar: { show: false }
            },
            series: [
                {
                name: "Intervenciones",
                data: data3
                }
            ],
            plotOptions: {
                bar: {
                horizontal: true,
                borderRadius: 4,
                barHeight: "70%"
                }
            },
            xaxis: {
                categories: labels3
            },
            colors: ["#4e73df"],
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                formatter: val => val + " intervenciones"
                }
            }
            };

            window.teacherCourseInterventionChart = new ApexCharts(
            document.querySelector("#teacherCourseInterventionChart"),
            options3
            );

            window.teacherCourseInterventionChart.render();

        })
        .fail(function (err) {
          console.error("Error cargando actividad docente:", err);
        });
    }
  };
});