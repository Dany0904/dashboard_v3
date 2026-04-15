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
      Ajax.call([{
        methodname: "local_dashboard_v3_get_user_activity_chart",
        args: {
          days: parseInt(days),
          courseid: parseInt(courseid)
        }
      }])[0].done(function (res) {

        let labels = [];
        let currentData = [];
        let previousData = [];

        res.current.forEach(item => {
          labels.push(item.label);
          currentData.push(item.value);
        });

        res.previous.forEach(item => {
          previousData.push(item.value);
        });

        if (window.activityChartInstance) {
          window.activityChartInstance.destroy();
        }

        var options = {
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
          options
        );

        window.activityChartInstance.render();

      }).fail(function (err) {
        console.error(err);
      });
    }
  };
});