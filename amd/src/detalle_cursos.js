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
      // AJAX
      // =========================
      const requests = Ajax.call([
        {
          methodname: "local_dashboard_v3_get_course_activity_chart",
          args: { days: days, courseid: courseid }
        },
        {
          methodname: "local_dashboard_v3_get_course_activity_weekday",
          args: { days: days, courseid: courseid }
        },
        {
          methodname: "local_dashboard_v3_get_course_top_modules",
          args: { days: days, courseid: courseid }
        }
      ]);
      $.when(requests[0], requests[1], requests[2]).done(function (activityRes, weekdayRes, modulesRes) {

        // =========================
        // GRÁFICA 1
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

        if (
          window.activityChartInstance &&
          typeof window.activityChartInstance.destroy === "function"
        ) {
          window.activityChartInstance.destroy();
        }

        const options = {
          chart: { type: "line", height: 350 },
          series: [
            { name: "Periodo actual", data: currentData },
            { name: "Periodo anterior", data: previousData }
          ],
          xaxis: { categories: labels },
          stroke: {
            curve: "smooth",
            width: [3, 2],
            dashArray: [0, 5]
          },
          colors: ["#4e73df", "#858796"]
        };

        window.activityChartInstance = new ApexCharts(
          document.querySelector("#activityChart"),
          options
        );

        window.activityChartInstance.render();

        // =========================
        // GRÁFICA: DÍAS SEMANA
        // =========================
        let labels2 = [];
        let data = [];

        weekdayRes.data.forEach(item => {
          labels2.push(item.label);
          data.push(item.value);
        });

        if (
          window.weekdayChart &&
          typeof window.weekdayChart.destroy === "function"
        ) {
          window.weekdayChart.destroy();
        }

        const options2 = {
          chart: {
            type: "bar",
            height: 350
          },
          series: [
            {
              name: "Eventos",
              data: data
            }
          ],
          xaxis: {
            categories: labels2
          },
          colors: ["#36b9cc"],
          tooltip: {
            y: {
              formatter: val => val + " eventos"
            }
          }
        };

        window.weekdayChart = new ApexCharts(
          document.querySelector("#weekdayChart"),
          options2
        );

        window.weekdayChart.render();

          // =========================
          // GRÁFICA: TOP MÓDULOS
          // =========================
          let labels3 = [];
          let data3 = [];

          modulesRes.data.forEach(item => {
            labels3.push(item.label);
            data3.push(item.value);
          });

          if (
            window.topModulesChart &&
            typeof window.topModulesChart.destroy === "function"
          ) {
            window.topModulesChart.destroy();
          }

          const options3 = {
            chart: {
              type: "bar",
              height: 350
            },
            series: [
              {
                name: "Eventos",
                data: data3
              }
            ],
            plotOptions: {
              bar: {
                horizontal: true
              }
            },
            xaxis: {
              categories: labels3
            },
            colors: ["#4e73df"],
            tooltip: {
              y: {
                formatter: val => val + " eventos"
              }
            }
          };

          window.topModulesChart = new ApexCharts(
            document.querySelector("#topModulesChart"),
            options3
          );

          window.topModulesChart.render();

      }).fail(function (err) {
        console.error(err);
      });
    }
  };
});